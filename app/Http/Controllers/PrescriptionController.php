<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\LabTest;
use App\Models\Medicine;
use App\Models\Patient;
use App\Models\Prescription;
use Illuminate\Http\Request;

class PrescriptionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $prescriptions = Prescription::with(['patient', 'doctor.user'])
            ->latest('prescription_date')
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

        return view('admin.pages.prescription.create', compact(
            'patients', 'doctors', 'appointments', 'medicines', 'labTests'
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
            'medicines' => 'nullable|array',
            'medicines.*.medicine_id' => 'required_with:medicines|exists:medicines,id',
            'medicines.*.dosage' => 'nullable|string|max:100',
            'medicines.*.duration' => 'nullable|string|max:50',
            'medicines.*.instructions' => 'nullable|string',
            'lab_tests' => 'nullable|array',
            'lab_tests.*.test_id' => 'required_with:lab_tests|exists:lab_tests,id',
        ]);

        $prescription = Prescription::create(
            collect($validated)->except(['medicines', 'lab_tests'])->toArray()
        );

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
        ]);

        return view('admin.pages.prescription.show', compact('prescription'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Prescription $prescription)
    {
        $prescription->load('prescriptionMedicines.medicine', 'labTestOrders');

        $patients = Patient::orderBy('name')->get();
        $doctors = Doctor::with('user')->get();
        $appointments = Appointment::with('patient')->orderBy('appointment_date', 'desc')->get();
        $medicines = Medicine::orderBy('name')->get();
        $labTests = LabTest::orderBy('test_name')->get();

        return view('admin.pages.prescription.edit', compact(
            'prescription', 'patients', 'doctors', 'appointments', 'medicines', 'labTests'
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
            'medicines' => 'nullable|array',
            'medicines.*.medicine_id' => 'required_with:medicines|exists:medicines,id',
            'medicines.*.dosage' => 'nullable|string|max:100',
            'medicines.*.duration' => 'nullable|string|max:50',
            'medicines.*.instructions' => 'nullable|string',
            'lab_tests' => 'nullable|array',
            'lab_tests.*.test_id' => 'required_with:lab_tests|exists:lab_tests,id',
        ]);

        $prescription->update(
            collect($validated)->except(['medicines', 'lab_tests'])->toArray()
        );

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
}