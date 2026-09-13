<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Doctor;
use App\Models\User;
use Illuminate\Http\Request;

class DoctorController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $doctors = Doctor::with(['user', 'department'])->latest()->paginate(10);

        $totalDoctors = Doctor::count();
        $totalDepartments = Department::count();
        $newThisMonth = Doctor::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        return view('admin.pages.doctor.index', compact(
            'doctors',
            'totalDoctors',
            'totalDepartments',
            'newThisMonth'
        ));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $users = User::orderBy('name')->get();
        $departments = Department::orderBy('name')->get();

        return view('admin.pages.doctor.create', compact('users', 'departments'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'department_id' => 'nullable|exists:departments,id',
            'specialization' => 'nullable|string|max:100',
            'qualification' => 'nullable|string|max:150',
            'consultation_fee' => 'nullable|numeric|min:0',
        ]);

        Doctor::create($validated);

        return redirect()
            ->route('doctors.index')
            ->with('success', 'Doctor added successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Doctor $doctor)
    {
        $doctor->load(['user', 'department']);

        return view('admin.pages.doctor.show', compact('doctor'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Doctor $doctor)
    {
        $users = User::orderBy('name')->get();
        $departments = Department::orderBy('name')->get();

        return view('admin.pages.doctor.edit', compact('doctor', 'users', 'departments'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Doctor $doctor)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'department_id' => 'nullable|exists:departments,id',
            'specialization' => 'nullable|string|max:100',
            'qualification' => 'nullable|string|max:150',
            'consultation_fee' => 'nullable|numeric|min:0',
        ]);

        $doctor->update($validated);

        return redirect()
            ->route('doctors.index')
            ->with('success', 'Doctor updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Doctor $doctor)
    {
        $doctor->delete();

        return redirect()
            ->route('doctors.index')
            ->with('success', 'Doctor deleted successfully.');
    }
}