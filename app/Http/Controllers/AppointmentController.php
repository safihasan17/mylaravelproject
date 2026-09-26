<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class AppointmentController extends Controller
{

    public function index(Request $request)
    {
        $query = Appointment::with(['patient', 'doctor.user', 'doctor.department']);


        if (Auth::user()->role_id == 2) {
            $query->whereHas('doctor', function ($q) {
                $q->where('user_id', Auth::user()->id);
            });
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('patient', function ($p) use ($search) {
                    $p->where('name', 'like', "%{$search}%");
                })->orWhereHas('doctor.user', function ($d) use ($search) {
                    $d->where('name', 'like', "%{$search}%");
                });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $appointments = $query->orderBy('id', 'desc')
            ->paginate(10)
            ->withQueryString();


        $statsQuery = Appointment::query();

        if (Auth::user()->role_id == 2) {
            $statsQuery->whereHas('doctor', function ($q) {
                $q->where('user_id', Auth::user()->id);
            });
        }

        $todayCount     = (clone $statsQuery)->whereDate('appointment_date', today())->count();
        $pendingCount   = (clone $statsQuery)->whereIn('status', ['Scheduled', 'Checked-in', 'Waiting'])->count();
        $completedCount = (clone $statsQuery)->where('status', 'Completed')->count();
        $cancelledCount = (clone $statsQuery)->where('status', 'Cancelled')->count();

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