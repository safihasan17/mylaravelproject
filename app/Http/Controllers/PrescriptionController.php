<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\LabTest;
use App\Models\Medicine;
use App\Models\Patient;
use App\Models\Prescription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Invoice;

class PrescriptionController extends Controller
{

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $prescriptions = Prescription::with(['patient', 'doctor.user', 'admission'])
            ->orderBy('id', 'desc')
            ->paginate(10);

        $totalPrescriptions = Prescription::count();
        $issuedToday = Prescription::whereDate('prescription_date', today())->count();
        $issuedThisMonth = Prescription::whereMonth('prescription_date', now()->month)
            ->whereYear('prescription_date', now()->year)
            ->count();

        return view('admin.pages.prescription.index', compact(
            'prescriptions',
            'totalPrescriptions',
            'issuedToday',
            'issuedThisMonth'
        ));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $patients = Patient::orderBy('name')->get();
        $doctors = Doctor::with('user')->get();
        $appointments = Appointment::with('patient')->orderBy('appointment_date', 'desc')->get();
        $medicines = Medicine::orderBy('name')->get();
        $labTests = LabTest::orderBy('test_name')->get();

        $appointmentMap = $appointments->mapWithKeys(fn($a) => [
            $a->id => ['patient_id' => $a->patient_id, 'doctor_id' => $a->doctor_id],
        ]);

        return view('admin.pages.prescription.create', compact(
            'patients',
            'doctors',
            'appointments',
            'medicines',
            'labTests',
            'appointmentMap'
        ));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'doctor_id' => 'required|exists:doctors,id',
            'appointment_id' => 'nullable|exists:appointments,id',
            'prescription_date' => 'required|date',
            'notes' => 'nullable|string',
            'admission_advised' => 'nullable|boolean',
            'medicines' => 'nullable|array',
            'medicines.*.medicine_id' => 'required_with:medicines|exists:medicines,id',
            'medicines.*.dosage' => 'nullable|string|max:100',
            'medicines.*.duration' => 'nullable|string|max:50',
            'medicines.*.instructions' => 'nullable|string',
            'lab_tests' => 'nullable|array',
            'lab_tests.*.test_id' => 'required_with:lab_tests|exists:lab_tests,id',
        ]);

        DB::transaction(function () use ($validated, $request) {
            $data = collect($validated)->except(['medicines', 'lab_tests'])->toArray();

            // Doctor can only SUGGEST admission. The receptionist does the
            // actual admission later, which fills prescriptions.admission_id.
            $data['admission_advised'] = $request->boolean('admission_advised');

            $prescription = Prescription::create($data);

            foreach ($validated['medicines'] ?? [] as $row) {
                $prescription->prescriptionMedicines()->create($row);
            }

            foreach ($validated['lab_tests'] ?? [] as $row) {
                $prescription->labTestOrders()->create([
                    'patient_id' => $prescription->patient_id,
                    'doctor_id' => $prescription->doctor_id,
                    'test_id' => $row['test_id'],
                    'status' => 'Pending',
                    'order_date' => $prescription->prescription_date,
                ]);
            }
        });

        return redirect()
            ->route('prescriptions.index')
            ->with('success', 'Prescription saved successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Prescription $prescription)
    {
        $prescription->load([
            'patient',
            'doctor.user',
            'doctor.department',
            'appointment',
            'prescriptionMedicines.medicine',
            'labTestOrders.test',
            'admission.ward',
            'admission.bed',
        ]);

        return view('admin.pages.prescription.show', compact('prescription'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Prescription $prescription)
    {
        $prescription->load('prescriptionMedicines.medicine', 'labTestOrders', 'admission.ward', 'admission.bed');

        $patients = Patient::orderBy('name')->get();
        $doctors = Doctor::with('user')->get();
        $appointments = Appointment::with('patient')->orderBy('appointment_date', 'desc')->get();
        $medicines = Medicine::orderBy('name')->get();
        $labTests = LabTest::orderBy('test_name')->get();

        $appointmentMap = $appointments->mapWithKeys(fn($a) => [
            $a->id => ['patient_id' => $a->patient_id, 'doctor_id' => $a->doctor_id],
        ]);

        $existingMedicineRows = $prescription->prescriptionMedicines->map(fn($row) => [
            'medicine_id' => $row->medicine_id,
            'dosage' => $row->dosage,
            'duration' => $row->duration,
            'instructions' => $row->instructions,
        ])->values();

        $existingLabTestRows = $prescription->labTestOrders
            ->where('status', 'Pending')
            ->map(fn($row) => ['test_id' => $row->test_id])
            ->values();

        return view('admin.pages.prescription.edit', compact(
            'prescription',
            'patients',
            'doctors',
            'appointments',
            'medicines',
            'labTests',
            'appointmentMap',
            'existingMedicineRows',
            'existingLabTestRows'
        ));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Prescription $prescription)
    {
        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'doctor_id' => 'required|exists:doctors,id',
            'appointment_id' => 'nullable|exists:appointments,id',
            'prescription_date' => 'required|date',
            'notes' => 'nullable|string',
            'admission_advised' => 'nullable|boolean',
            'medicines' => 'nullable|array',
            'medicines.*.medicine_id' => 'required_with:medicines|exists:medicines,id',
            'medicines.*.dosage' => 'nullable|string|max:100',
            'medicines.*.duration' => 'nullable|string|max:50',
            'medicines.*.instructions' => 'nullable|string',
            'lab_tests' => 'nullable|array',
            'lab_tests.*.test_id' => 'required_with:lab_tests|exists:lab_tests,id',
        ]);

        DB::transaction(function () use ($validated, $request, $prescription) {
            $data = collect($validated)->except(['medicines', 'lab_tests'])->toArray();

            // Once the receptionist has admitted the patient, the suggestion
            // stays "true" (the form no longer shows the radio buttons).
            $data['admission_advised'] = $prescription->admission_id
                ? true
                : $request->boolean('admission_advised');

            $prescription->update($data);

            // Replace the medicine list with whatever was submitted this time
            $prescription->prescriptionMedicines()->delete();
            foreach ($validated['medicines'] ?? [] as $row) {
                $prescription->prescriptionMedicines()->create($row);
            }

            $prescription->labTestOrders()->where('status', 'Pending')->delete();
            foreach ($validated['lab_tests'] ?? [] as $row) {
                $prescription->labTestOrders()->create([
                    'patient_id' => $prescription->patient_id,
                    'doctor_id' => $prescription->doctor_id,
                    'test_id' => $row['test_id'],
                    'status' => 'Pending',
                    'order_date' => $prescription->prescription_date,
                ]);
            }
        });

        return redirect()
            ->route('prescriptions.index')
            ->with('success', 'Prescription updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Prescription $prescription)
    {
        $prescription->delete();

        return redirect()
            ->route('prescriptions.index')
            ->with('success', 'Prescription deleted successfully.');
    }




    // invoice
    public function generateInvoice(Prescription $prescription)
    {
        $prescription->load(['doctor', 'labTestOrders.test', 'medicines']);

        if ($prescription->admission_id) {
            $invoice = Invoice::firstOrNew(['admission_id' => $prescription->admission_id]);
        } elseif ($prescription->appointment_id) {
            $invoice = Invoice::firstOrNew(['appointment_id' => $prescription->appointment_id]);
        } else {
            return back()->with('error', 'This prescription is not linked to an appointment or admission, so an invoice cannot be generated.');
        }

        $invoice->patient_id = $prescription->patient_id;
        $invoice->invoice_date = $invoice->invoice_date ?? $prescription->prescription_date;
        $invoice->status = $invoice->status ?? 'Unpaid';
        $invoice->save();

        
        $invoice->items()->whereIn('item_type', ['Consultation Fee', 'Lab Test', 'Medicine'])->delete();

        $isAdmitted = (bool) $prescription->admission_id;

        if ($prescription->doctor && $prescription->doctor->consultation_fee) {
            $invoice->items()->create([
                'item_type' => 'Consultation Fee',
                'item_reference_id' => $prescription->doctor_id,
                'description' => 'Consultation — Dr. ' . ($prescription->doctor->user->name ?? 'N/A'),
                'amount' => $prescription->doctor->consultation_fee,
            ]);
        }

        
        if ($isAdmitted) {
            foreach ($prescription->medicines as $medicine) {
                $invoice->items()->create([
                    'item_type' => 'Medicine',
                    'item_reference_id' => $medicine->id,
                    'description' => $medicine->name,
                    'amount' => $medicine->unit_price ?? 0,
                ]);
            }
        }

        // Lab tests ordered by the doctor — always billed (cancelled ones are skipped)
        foreach ($prescription->labTestOrders as $order) {
            if ($order->test && $order->status !== 'Cancelled') {
                $invoice->items()->create([
                    'item_type' => 'Lab Test',
                    'item_reference_id' => $order->test_id,
                    'description' => $order->test->test_name,
                    'amount' => $order->test->price ?? 0,
                ]);
            }
        }

        $invoice->update(['total_amount' => $invoice->items()->sum('amount')]);

        return redirect()
            ->route('invoices.show', $invoice->id)
            ->with('success', 'Invoice generated from prescription.');
    }
}