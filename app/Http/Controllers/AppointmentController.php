<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AppointmentController extends Controller
{
    
    public function index(Request $request)
    {
        $query = Appointment::with(['patient', 'doctor.user', 'doctor.department']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $appointments = $query->latest()
            ->paginate(10)
            ->withQueryString();

        $todayCount     = Appointment::whereDate('appointment_date', today())->count();
        $pendingCount   = Appointment::whereIn('status', ['Scheduled', 'Checked-in', 'Waiting'])->count();
        $completedCount = Appointment::where('status', 'Completed')->count();
        $cancelledCount = Appointment::where('status', 'Cancelled')->count();

        return view('admin.pages.appointment.index', compact(
            'appointments',
            'todayCount',
            'pendingCount',
            'completedCount',
            'cancelledCount'
        ));
    }

    
    public function create()
    {
        $patients = Patient::orderBy('name')->get();
        $doctors  = Doctor::with(['user', 'department'])->get();

        return view('admin.pages.appointment.create', compact('patients', 'doctors'));
    }

    
    public function store(Request $request)
    {
        $validated = $this->validateAppointment($request);

        Appointment::create($validated);

        return redirect()->route('appointments.index')
            ->with('success', 'Appointment booked successfully.');
    }

    
    public function show(Appointment $appointment)
    {
        $appointment->load(['patient', 'doctor.user', 'doctor.department']);

        return view('admin.pages.appointment.show', compact('appointment'));
    }

    
    public function edit(Appointment $appointment)
    {
        $patients = Patient::orderBy('name')->get();
        $doctors  = Doctor::with(['user', 'department'])->get();

        return view('admin.pages.appointment.edit', compact('appointment', 'patients', 'doctors'));
    }

    
    public function update(Request $request, Appointment $appointment)
    {
        $validated = $this->validateAppointment($request);

        $appointment->update($validated);

        return redirect()->route('appointments.index')
            ->with('success', 'Appointment updated successfully.');
    }

    
    public function destroy(Appointment $appointment)
    {
        $appointment->delete();

        return redirect()->route('appointments.index')
            ->with('success', 'Appointment deleted successfully.');
    }

    
    private function validateAppointment(Request $request): array
    {
        return $request->validate([
            'patient_id'        => ['required', 'exists:patients,id'],
            'doctor_id'         => ['required', 'exists:doctors,id'],
            'appointment_date'  => ['required', 'date'],
            'appointment_time'  => ['required'],
            'status'            => ['required', Rule::in(['Scheduled', 'Checked-in', 'Waiting', 'Completed', 'Cancelled'])],
            'reason'            => ['nullable', 'string', 'max:1000'],
        ]);
    }
}