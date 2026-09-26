<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Doctor;
use App\Models\User;
use App\Services\UploadImgService;
use Illuminate\Http\Request;

class DoctorController extends Controller
{

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


    public function create()
    {
        $users = User::orderBy('name')->get();
        $departments = Department::orderBy('name')->get();

        return view('admin.pages.doctor.create', compact('users', 'departments'));
    }


    public function store(Request $request)
    {
        $request->validate([
            'image' => 'image|mimes:jpeg,png,jpg,gif,svg|max:500',
        ], [
            'image.max' => 'Image size is too learge . Maximum size is 500kb',
        ]);

        if ($request->hasFile('image')) {
            $imgName = UploadImgService::upload($request->image, 'uploads/doctors');

            Doctor::create([
                'user_id' => $request->user_id,
                'department_id' => $request->department_id,
                'specialization' => $request->specialization,
                'qualification' => $request->qualification,
                'consultation_fee' => $request->consultation_fee,
                'image'            => $imgName
            ]);
            return redirect()->route('doctors.index')->with('success', 'Doctor created successfully');
        } else {
            Doctor::create([
                'user_id' => $request->user_id,
                'department_id' => $request->department_id,
                'specialization' => $request->specialization,
                'qualification' => $request->qualification,
                'consultation_fee' => $request->consultation_fee,

            ]);
            return redirect()->route('doctors.index')->with('success', 'Doctor created successfully');
        }
    }


    public function show(Doctor $doctor)
    {
        $doctor->load(['user', 'department']);

        return view('admin.pages.doctor.show', compact('doctor'));
    }


    public function edit(Doctor $doctor)
    {
        $users = User::orderBy('name')->get();
        $departments = Department::orderBy('name')->get();

        return view('admin.pages.doctor.edit', compact('doctor', 'users', 'departments'));
    }


    public function update(Request $request, Doctor $doctor)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'department_id' => 'nullable|exists:departments,id',
            'specialization' => 'nullable|string|max:100',
            'qualification' => 'nullable|string|max:150',
            'consultation_fee' => 'nullable|numeric|min:0',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:500',
        ], [
            'image.max' => 'Image size is too large. Maximum size is 500KB',
        ]);


        if ($request->hasFile('image')) {

            $imgName = UploadImgService::upload(
                $request->image,
                'uploads/doctors'
            );

            $validated['image'] = $imgName;
        }

        $doctor->update($validated);

        return redirect()
            ->route('doctors.index')
            ->with('success', 'Doctor updated successfully.');
    }


    public function destroy(Doctor $doctor)
    {
        $doctor->delete();

        return redirect()
            ->route('doctors.index')
            ->with('success', 'Doctor deleted successfully.');
    }


    public function search(Request $request)
    {
        $search = $request->query('q');

        $doctors = Doctor::with(['user', 'department'])
            ->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            })
            ->limit(10)
            ->get()
            ->map(function ($doctor) {
                return [
                    'id'   => $doctor->id,
                    'name' => ($doctor->user->name ?? 'Doctor #' . $doctor->id)
                        . ($doctor->department ? ' — ' . $doctor->department->name : ''),
                ];
            });

        return response()->json($doctors);
    }
}
