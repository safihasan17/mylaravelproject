<?php

namespace App\Http\Controllers;

use App\Models\Admission;
use App\Models\Appointment;
use App\Models\Invoice;
use App\Models\Patient;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $invoices = Invoice::with(['patient', 'admission', 'appointment'])
            ->orderBy('id', 'desc')
            ->paginate(10);

        $totalInvoices = Invoice::count();
        $unpaidCount = Invoice::where('status', 'Unpaid')->count();
        $paidCount = Invoice::where('status', 'Paid')->count();
        $totalDue = Invoice::whereIn('status', ['Unpaid', 'Partially Paid'])
            ->get()
            ->sum(fn ($invoice) => $invoice->total_amount - $invoice->paid_amount);

        return view('admin.pages.invoice.index', compact(
            'invoices', 'totalInvoices', 'unpaidCount', 'paidCount', 'totalDue'
        ));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $patients = Patient::orderBy('name')->get();
        $admissions = Admission::with('patient')->where('status', 'Admitted')->get();
        $appointments = Appointment::with('patient')->orderBy('appointment_date', 'desc')->get();

        return view('admin.pages.invoice.create', compact('patients', 'admissions', 'appointments'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'admission_id' => 'nullable|exists:admissions,id',
            'appointment_id' => 'nullable|exists:appointments,id',
            'total_amount' => 'required|numeric|min:0',
            'paid_amount' => 'nullable|numeric|min:0',
            'status' => 'required|in:Unpaid,Partially Paid,Paid,Cancelled',
            'invoice_date' => 'required|date',
        ]);

        Invoice::create($validated);

        return redirect()
            ->route('invoices.index')
            ->with('success', 'Invoice created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Invoice $invoice)
    {
        $invoice->load(['patient', 'admission.ward', 'admission.bed', 'appointment.doctor.user']);

        return view('admin.pages.invoice.show', compact('invoice'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Invoice $invoice)
    {
        $patients = Patient::orderBy('name')->get();

        $admissions = Admission::with('patient')
            ->where('status', 'Admitted')
            ->orWhere('id', $invoice->admission_id)
            ->get();

        $appointments = Appointment::with('patient')->orderBy('appointment_date', 'desc')->get();

        return view('admin.pages.invoice.edit', compact('invoice', 'patients', 'admissions', 'appointments'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Invoice $invoice)
    {
        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'admission_id' => 'nullable|exists:admissions,id',
            'appointment_id' => 'nullable|exists:appointments,id',
            'total_amount' => 'required|numeric|min:0',
            'paid_amount' => 'nullable|numeric|min:0',
            'status' => 'required|in:Unpaid,Partially Paid,Paid,Cancelled',
            'invoice_date' => 'required|date',
        ]);

        $invoice->update($validated);

        return redirect()
            ->route('invoices.index')
            ->with('success', 'Invoice updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Invoice $invoice)
    {
        $invoice->delete();

        return redirect()
            ->route('invoices.index')
            ->with('success', 'Invoice deleted successfully.');
    }
}