<?php

namespace App\Http\Controllers;

use App\Models\Admission;
use App\Models\Bed;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\Ward;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdmissionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $admissions = Admission::with(['patient', 'doctor.user', 'ward', 'bed'])
            ->orderBy('id', 'desc')
            ->paginate(10);

        $totalAdmissions = Admission::count();
        $currentlyAdmitted = Admission::where('status', 'Admitted')->count();
        $dischargedCount = Admission::where('status', 'Discharged')->count();

        return view('admin.pages.admission.index', compact(
            'admissions', 'totalAdmissions', 'currentlyAdmitted', 'dischargedCount'
        ));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        
        $prescription = null;
        if ($request->filled('prescription_id')) {
            $prescription = Prescription::with(['patient', 'doctor.user'])
                ->findOrFail($request->query('prescription_id'));

            if ($prescription->admission_id) {
                return redirect()
                    ->route('admissions.show', $prescription->admission_id)
                    ->with('error', 'This prescription already has an admission.');
            }
        }

        $patients = Patient::orderBy('name')->get();
        $doctors = Doctor::with('user')->get();
        $wards = Ward::orderBy('name')->get();
        $beds = Bed::with('ward')->where('status', 'Available')->get();

        return view('admin.pages.admission.create', compact('patients', 'doctors', 'wards', 'beds', 'prescription'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'prescription_id' => 'nullable|exists:prescriptions,id',
            'patient_id' => 'required|exists:patients,id',
            'doctor_id' => 'required|exists:doctors,id',
            'ward_id' => 'required|exists:wards,id',
            'bed_id' => 'required|exists:beds,id',
            'admission_date' => 'required|date',
            'discharge_date' => 'nullable|date|after_or_equal:admission_date',
            'status' => 'required|in:Admitted,Discharged,Transferred',
        ]);

        // If this admission comes from a prescription, make sure it is valid
        $prescription = null;
        if (! empty($validated['prescription_id'])) {
            $prescription = Prescription::find($validated['prescription_id']);

            if ($prescription->admission_id) {
                return back()
                    ->withInput()
                    ->withErrors(['patient_id' => 'This prescription is already linked to an admission.']);
            }

            if ((int) $prescription->patient_id !== (int) $validated['patient_id']) {
                return back()
                    ->withInput()
                    ->withErrors(['patient_id' => 'The patient does not match the prescription.']);
            }
        }

        // A patient can only have ONE active admission at a time
        if ($validated['status'] === 'Admitted' && $this->hasActiveAdmission($validated['patient_id'])) {
            return back()
                ->withInput()
                ->withErrors(['patient_id' => 'This patient is already admitted. Discharge the current admission first.']);
        }

        DB::transaction(function () use ($validated, $prescription) {
            $admission = Admission::create(collect($validated)->except('prescription_id')->toArray());

            // Mark the bed occupied once a patient is admitted to it
            if ($admission->status === 'Admitted') {
                $admission->bed->update(['status' => 'Occupied']);
            }

            // Link the prescription -> its status becomes "Admitted"
            if ($prescription) {
                $prescription->update(['admission_id' => $admission->id]);
            }
        });

        return redirect()
            ->route('admissions.index')
            ->with('success', 'Admission recorded successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Admission $admission)
    {
        $admission->load(['patient', 'doctor.user', 'doctor.department', 'ward', 'bed']);

        return view('admin.pages.admission.show', compact('admission'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Admission $admission)
    {
        $patients = Patient::orderBy('name')->get();
        $doctors = Doctor::with('user')->get();
        $wards = Ward::orderBy('name')->get();

        
        $beds = Bed::with('ward')
            ->where('status', 'Available')
            ->orWhere('id', $admission->bed_id)
            ->get();

        return view('admin.pages.admission.edit', compact('admission', 'patients', 'doctors', 'wards', 'beds'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Admission $admission)
    {
        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'doctor_id' => 'required|exists:doctors,id',
            'ward_id' => 'required|exists:wards,id',
            'bed_id' => 'required|exists:beds,id',
            'admission_date' => 'required|date',
            'discharge_date' => 'nullable|date|after_or_equal:admission_date',
            'status' => 'required|in:Admitted,Discharged,Transferred',
        ]);

        if ($validated['status'] === 'Admitted'
            && $this->hasActiveAdmission($validated['patient_id'], $admission->id)) {
            return back()
                ->withInput()
                ->withErrors(['patient_id' => 'This patient already has another active admission.']);
        }

        $previousBedId = $admission->bed_id;

        $admission->update($validated);

        // Keep bed status in sync with the admission status
        if ($admission->status === 'Discharged') {
            $admission->bed->update(['status' => 'Available']);
        } elseif ($admission->status === 'Admitted') {
            $admission->bed->update(['status' => 'Occupied']);

            // Free up the old bed if the patient was moved to a new one
            if ($previousBedId != $admission->bed_id) {
                Bed::where('id', $previousBedId)->update(['status' => 'Available']);
            }
        }

        return redirect()
            ->route('admissions.index')
            ->with('success', 'Admission updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Admission $admission)
    {
        
        if ($admission->status === 'Admitted') {
            Bed::where('id', $admission->bed_id)->update(['status' => 'Available']);
        }

        $admission->delete();

        return redirect()
            ->route('admissions.index')
            ->with('success', 'Admission deleted successfully.');
    }

    /**
     * Does this patient currently have an Admitted (active) admission?
     */
    private function hasActiveAdmission(int|string $patientId, ?int $exceptId = null): bool
    {
        return Admission::where('patient_id', $patientId)
            ->where('status', 'Admitted')
            ->when($exceptId, fn ($q) => $q->where('id', '!=', $exceptId))
            ->exists();
    }
}