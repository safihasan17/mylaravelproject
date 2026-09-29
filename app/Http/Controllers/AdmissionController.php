<?php

namespace App\Http\Controllers;

use App\Models\Admission;
use App\Models\Bed;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Ward;
use Illuminate\Http\Request;

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
    public function create()
    {
        $patients = Patient::orderBy('name')->get();
        $doctors = Doctor::with('user')->get();
        $wards = Ward::orderBy('name')->get();
        $beds = Bed::with('ward')->where('status', 'Available')->get();

        return view('admin.pages.admission.create', compact('patients', 'doctors', 'wards', 'beds'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
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

        // A patient can only have ONE active admission at a time
        if ($validated['status'] === 'Admitted' && $this->hasActiveAdmission($validated['patient_id'])) {
            return back()
                ->withInput()
                ->withErrors(['patient_id' => 'This patient is already admitted. Discharge the current admission first.']);
        }

        $admission = Admission::create($validated);

        // Mark the bed occupied once a patient is admitted to it
        if ($admission->status === 'Admitted') {
            $admission->bed->update(['status' => 'Occupied']);
        }

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

        // Include the currently assigned bed even if it's not "Available"
        // anymore, otherwise it would disappear from its own edit form.
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
        // Free the bed only if this admission was still occupying it.
        // (A discharged admission's bed may already belong to someone else.)
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