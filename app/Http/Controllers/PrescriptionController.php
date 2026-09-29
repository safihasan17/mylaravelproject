<?php

namespace App\Http\Controllers;

use App\Models\Admission;
use App\Models\Appointment;
use App\Models\Bed;
use App\Models\Doctor;
use App\Models\LabTest;
use App\Models\Medicine;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\Ward;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

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
        $wards = Ward::orderBy('name')->get();
        $beds = Bed::where('status', 'Available')->get();

        $appointmentMap = $appointments->mapWithKeys(fn ($a) => [
            $a->id => ['patient_id' => $a->patient_id, 'doctor_id' => $a->doctor_id],
        ]);

        return view('admin.pages.prescription.create', compact(
            'patients', 'doctors', 'appointments', 'medicines', 'labTests', 'appointmentMap',
            'wards', 'beds'
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
        ] + $this->admissionRules($request));

        DB::transaction(function () use ($validated) {
            $prescription = Prescription::create(
                collect($validated)->except(['medicines', 'lab_tests', 'admit_patient', 'admission'])->toArray()
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

            // Doctor chose "Admit patient" -> create the admission
            // (otherwise admission_id simply stays NULL)
            if (($validated['admit_patient'] ?? '0') === '1') {
                $this->admitPatient($prescription, $validated['admission']);
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

        $appointmentMap = $appointments->mapWithKeys(fn ($a) => [
            $a->id => ['patient_id' => $a->patient_id, 'doctor_id' => $a->doctor_id],
        ]);

        $existingMedicineRows = $prescription->prescriptionMedicines->map(fn ($row) => [
            'medicine_id' => $row->medicine_id,
            'dosage' => $row->dosage,
            'duration' => $row->duration,
            'instructions' => $row->instructions,
        ])->values();

        $existingLabTestRows = $prescription->labTestOrders
            ->where('status', 'Pending')
            ->map(fn ($row) => ['test_id' => $row->test_id])
            ->values();

        $wards = Ward::orderBy('name')->get();
        $beds = Bed::where('status', 'Available')->get();

        return view('admin.pages.prescription.edit', compact(
            'prescription', 'patients', 'doctors', 'appointments', 'medicines', 'labTests',
            'appointmentMap', 'existingMedicineRows', 'existingLabTestRows', 'wards', 'beds'
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
        ] + $this->admissionRules($request));

        DB::transaction(function () use ($validated, $prescription) {
            $prescription->update(
                collect($validated)->except(['medicines', 'lab_tests', 'admit_patient', 'admission'])->toArray()
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

            // Admit only if not already admitted from this prescription.
            // (Managing an existing admission is done from the Admissions page.)
            if (is_null($prescription->admission_id) && ($validated['admit_patient'] ?? '0') === '1') {
                $this->admitPatient($prescription, $validated['admission']);
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

    /**
     * Validation rules for the optional "admit patient" section.
     */
    private function admissionRules(Request $request): array
    {
        return [
            'admit_patient' => 'nullable|in:0,1',
            'admission.ward_id' => 'required_if:admit_patient,1|nullable|exists:wards,id',
            'admission.bed_id' => [
                'required_if:admit_patient,1',
                'nullable',
                Rule::exists('beds', 'id')->where(fn ($q) => $q
                    ->where('status', 'Available')
                    ->where('ward_id', $request->input('admission.ward_id'))),
            ],
            'admission.admission_date' => 'required_if:admit_patient,1|nullable|date',
        ];
    }

    /**
     * Create an Admission from this prescription, occupy the bed
     * and link it via prescriptions.admission_id.
     * Must be called inside a DB transaction.
     */
    private function admitPatient(Prescription $prescription, array $data): void
    {
        // A patient can only have ONE active admission at a time
        $alreadyAdmitted = Admission::where('patient_id', $prescription->patient_id)
            ->where('status', 'Admitted')
            ->exists();

        if ($alreadyAdmitted) {
            throw ValidationException::withMessages([
                'admit_patient' => 'This patient is already admitted. Discharge the current admission first.',
            ]);
        }

        // Lock the bed so two doctors can't take the same bed at once
        $bed = Bed::whereKey($data['bed_id'])->lockForUpdate()->first();

        if (! $bed || $bed->status !== 'Available') {
            throw ValidationException::withMessages([
                'admission.bed_id' => 'This bed was just taken. Please choose another bed.',
            ]);
        }

        $admission = Admission::create([
            'patient_id' => $prescription->patient_id,
            'doctor_id' => $prescription->doctor_id,
            'ward_id' => $data['ward_id'],
            'bed_id' => $bed->id,
            'admission_date' => $data['admission_date'],
            'status' => 'Admitted',
        ]);

        $bed->update(['status' => 'Occupied']);

        $prescription->update(['admission_id' => $admission->id]);
    }
}