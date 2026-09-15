<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Medicine;
use App\Models\Patient;
use App\Models\Prescription;
use Illuminate\Http\Request;

class PrescriptionController extends Controller
{
    
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

    
    public function create()
    {
        $patients = Patient::orderBy('name')->get();
        $doctors = Doctor::with('user')->get();
        $appointments = Appointment::with('patient')->orderBy('appointment_date', 'desc')->get();
        $medicines = Medicine::orderBy('name')->get();

        return view('admin.pages.prescription.create', compact('patients', 'doctors', 'appointments', 'medicines'));
    }

    
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
        ]);

        $prescription = Prescription::create(collect($validated)->except('medicines')->toArray());

        foreach ($validated['medicines'] ?? [] as $row) {
            $prescription->prescriptionMedicines()->create($row);
        }

        return redirect()
            ->route('prescriptions.index')
            ->with('success', 'Prescription saved successfully.');
    }

    
    public function show(Prescription $prescription)
    {
        $prescription->load([
            'patient',
            'doctor.user',
            'doctor.department',
            'appointment',
            'prescriptionMedicines.medicine',
        ]);

        return view('admin.pages.prescription.show', compact('prescription'));
    }

    
    public function edit(Prescription $prescription)
    {
        $prescription->load('prescriptionMedicines.medicine');

        $patients = Patient::orderBy('name')->get();
        $doctors = Doctor::with('user')->get();
        $appointments = Appointment::with('patient')->orderBy('appointment_date', 'desc')->get();
        $medicines = Medicine::orderBy('name')->get();

        return view('admin.pages.prescription.edit', compact(
            'prescription', 'patients', 'doctors', 'appointments', 'medicines'
        ));
    }

   
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
        ]);

        $prescription->update(collect($validated)->except('medicines')->toArray());

       
        $prescription->prescriptionMedicines()->delete();
        foreach ($validated['medicines'] ?? [] as $row) {
            $prescription->prescriptionMedicines()->create($row);
        }

        return redirect()
            ->route('prescriptions.index')
            ->with('success', 'Prescription updated successfully.');
    }

   
    public function destroy(Prescription $prescription)
    {
        $prescription->delete();

        return redirect()
            ->route('prescriptions.index')
            ->with('success', 'Prescription deleted successfully.');
    }
}