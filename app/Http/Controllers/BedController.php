<?php

namespace App\Http\Controllers;

use App\Models\Bed;
use App\Models\Ward;
use Illuminate\Http\Request;

class BedController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $beds = Bed::with('ward')->orderBy('ward_id')->orderBy('bed_number')->paginate(15);

        $totalBeds = Bed::count();
        $availableCount = Bed::where('status', 'Available')->count();
        $occupiedCount = Bed::where('status', 'Occupied')->count();
        $maintenanceCount = Bed::where('status', 'Maintenance')->count();

        return view('admin.pages.bed.index', compact(
            'beds', 'totalBeds', 'availableCount', 'occupiedCount', 'maintenanceCount'
        ));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $wards = Ward::orderBy('name')->get();

        return view('admin.pages.bed.create', compact('wards'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'ward_id' => 'required|exists:wards,id',
            'bed_number' => 'required|string|max:20',
            'status' => 'required|in:Available,Occupied,Reserved,Maintenance',
        ]);

        Bed::create($validated);

        return redirect()
            ->route('beds.index')
            ->with('success', 'Bed added successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Bed $bed)
    {
        $bed->load('ward');

        return view('admin.pages.bed.show', compact('bed'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Bed $bed)
    {
        $wards = Ward::orderBy('name')->get();

        return view('admin.pages.bed.edit', compact('bed', 'wards'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Bed $bed)
    {
        $validated = $request->validate([
            'ward_id' => 'required|exists:wards,id',
            'bed_number' => 'required|string|max:20',
            'status' => 'required|in:Available,Occupied,Reserved,Maintenance',
        ]);

        $bed->update($validated);

        return redirect()
            ->route('beds.index')
            ->with('success', 'Bed updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Bed $bed)
    {
        $bed->delete();

        return redirect()
            ->route('beds.index')
            ->with('success', 'Bed deleted successfully.');
    }
}