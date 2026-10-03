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
            'invoice_date' => 'required|date',
        ]);

        // Paid amount / status come from Payments, never typed by hand.
        Invoice::create($validated + ['paid_amount' => 0, 'status' => 'Unpaid']);

        return redirect()
            ->route('invoices.index')
            ->with('success', 'Invoice created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Invoice $invoice)
    {
        $invoice->load(['patient', 'admission.ward', 'admission.bed', 'appointment.doctor.user', 'payments.receiver']);

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
            // total can never drop below what has already been paid
            'total_amount' => 'required|numeric|min:' . max(0, (float) $invoice->paid_amount),
            'status' => 'nullable|in:Active,Cancelled',
            'invoice_date' => 'required|date',
        ]);

        $newStatus = $validated['status'] ?? null;
        unset($validated['status']);

        $invoice->fill($validated);

        // Only the admin can cancel / re-open an invoice. Everything else is automatic.
        if ($newStatus && auth()->user()->role_id == 1) {
            $invoice->status = $newStatus === 'Cancelled' ? 'Cancelled' : 'Unpaid';
        }

        $invoice->save();

        return redirect()
            ->route('invoices.index')
            ->with('success', 'Invoice updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Invoice $invoice)
    {
        if ($invoice->payments()->exists()) {
            return redirect()
                ->route('invoices.index')
                ->with('error', 'This invoice has payment records. Delete its payments first (Payments page).');
        }

        $invoice->delete();

        return redirect()
            ->route('invoices.index')
            ->with('success', 'Invoice deleted successfully.');
    }

    /**
     * Doctor-fee invoice created straight from an appointment
     * (patient came, saw the doctor, no tests / admission).
     * Calling it again does not duplicate the fee.
     */
    public function fromAppointment(Appointment $appointment)
    {
        $appointment->load('doctor.user');

        $invoice = Invoice::firstOrNew(['appointment_id' => $appointment->id]);

        if (! $invoice->exists) {
            $invoice->patient_id = $appointment->patient_id;
            $invoice->invoice_date = $appointment->appointment_date ?? now();
            $invoice->status = 'Unpaid';
            $invoice->total_amount = 0;
            $invoice->save();
        }

        $fee = $appointment->doctor?->consultation_fee;

        if ($fee && ! $invoice->items()->where('item_type', 'Consultation Fee')->exists()) {
            $invoice->items()->create([
                'item_type' => 'Consultation Fee',
                'item_reference_id' => $appointment->doctor_id,
                'description' => 'Consultation — Dr. ' . ($appointment->doctor->user->name ?? 'N/A'),
                'amount' => $fee,
            ]);
        }

        $invoice->update(['total_amount' => $invoice->items()->sum('amount')]);

        return redirect()
            ->route('invoices.show', $invoice->id)
            ->with('success', 'Doctor fee invoice is ready to print.');
    }
}