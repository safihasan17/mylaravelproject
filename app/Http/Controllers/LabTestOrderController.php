<?php

namespace App\Http\Controllers;

use App\Models\Doctor;
use App\Models\LabTest;
use App\Models\Invoice;
use App\Models\LabTestOrder;
use App\Models\Patient;
use Illuminate\Http\Request;

class LabTestOrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $labTestOrders = LabTestOrder::with(['patient', 'doctor.user', 'test'])
            ->latest('order_date')
            ->paginate(10);

        $pendingCount = LabTestOrder::where('status', 'Pending')->count();
        $inProgressCount = LabTestOrder::where('status', 'In Progress')->count();
        $completedCount = LabTestOrder::where('status', 'Completed')->count();
        $cancelledCount = LabTestOrder::where('status', 'Cancelled')->count();

        return view('admin.pages.lab-test-order.index', compact(
            'labTestOrders',
            'pendingCount',
            'inProgressCount',
            'completedCount',
            'cancelledCount'
        ));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $patients = Patient::orderBy('name')->get();
        $doctors = Doctor::with('user')->get();
        $labTests = LabTest::orderBy('test_name')->get();

        return view('admin.pages.lab-test-order.create', compact('patients', 'doctors', 'labTests'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'doctor_id' => 'required|exists:doctors,id',
            'test_id' => 'required|exists:lab_tests,id',
            'status' => 'required|in:Pending,In Progress,Completed,Cancelled',
            'result' => 'nullable|string',
            'order_date' => 'required|date',
        ]);

        LabTestOrder::create($validated);

        return redirect()
            ->route('lab-test-orders.index')
            ->with('success', 'Lab test order created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(LabTestOrder $labTestOrder)
    {
        $labTestOrder->load(['patient', 'doctor.user', 'doctor.department', 'test']);

        return view('admin.pages.lab-test-order.show', compact('labTestOrder'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(LabTestOrder $labTestOrder)
    {
        $patients = Patient::orderBy('name')->get();
        $doctors = Doctor::with('user')->get();
        $labTests = LabTest::orderBy('test_name')->get();

        return view('admin.pages.lab-test-order.edit', compact('labTestOrder', 'patients', 'doctors', 'labTests'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, LabTestOrder $labTestOrder)
    {
        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'doctor_id' => 'required|exists:doctors,id',
            'test_id' => 'required|exists:lab_tests,id',
            'status' => 'required|in:Pending,In Progress,Completed,Cancelled',
            'result' => 'nullable|string',
            'order_date' => 'required|date',
        ]);

        $labTestOrder->update($validated);

        return redirect()
            ->route('lab-test-orders.index')
            ->with('success', 'Lab test order updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(LabTestOrder $labTestOrder)
    {
        $labTestOrder->delete();

        return redirect()
            ->route('lab-test-orders.index')
            ->with('success', 'Lab test order deleted successfully.');
    }

    
    public function generateInvoice(LabTestOrder $labTestOrder)
    {
        if ($labTestOrder->prescription_id) {
            return back()->with('error', 'This test belongs to a prescription. Generate the invoice from the prescription.');
        }

        if ($labTestOrder->status === 'Cancelled') {
            return back()->with('error', 'A cancelled test cannot be billed.');
        }

        $labTestOrder->load('test');

       
        $invoice = Invoice::where('patient_id', $labTestOrder->patient_id)
            ->whereNull('admission_id')
            ->whereNull('appointment_id')
            ->whereDate('invoice_date', today())
            ->where('status', 'Unpaid')
            ->first();

        if (! $invoice) {
            $invoice = Invoice::create([
                'patient_id' => $labTestOrder->patient_id,
                'invoice_date' => today(),
                'status' => 'Unpaid',
                'total_amount' => 0,
            ]);
        }

        $alreadyBilled = $invoice->items()
            ->where('item_type', 'Lab Test')
            ->where('item_reference_id', $labTestOrder->test_id)
            ->exists();

        if (! $alreadyBilled) {
            $invoice->items()->create([
                'item_type' => 'Lab Test',
                'item_reference_id' => $labTestOrder->test_id,
                'description' => $labTestOrder->test->test_name ?? 'Lab Test',
                'amount' => $labTestOrder->test->price ?? 0,
            ]);
        }

        $invoice->update(['total_amount' => $invoice->items()->sum('amount')]);

        return redirect()
            ->route('invoices.show', $invoice->id)
            ->with('success', 'Test invoice is ready to print.');
    }
}