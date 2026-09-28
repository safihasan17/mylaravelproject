<?php

namespace App\Http\Controllers;

use App\Models\Ward;
use Illuminate\Http\Request;

class WardController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $totalWards = Ward::count();

        $totalFloors = Ward::whereNotNull('floor')->distinct()->count('floor');

        $totalTypes = Ward::whereNotNull('type')->distinct()->count('type');

        $wards = Ward::orderby('id', 'desc')->get();
        return view('admin.pages.ward.index', compact('wards', 'totalWards', 'totalFloors', 'totalTypes'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $floors = $this->floorOptions();
        $types = $this->typeOptions();
        return view('admin.pages.ward.create', compact('floors', 'types'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // dd($request->all());
        $request->validate([
            'name' => 'required|min:3|max:100|unique:wards,name',
            'floor' => 'required|max:20',
            'type' => 'required|max:50'
        ]);

        $ward        = new Ward();
        $ward->name  = $request->name;
        $ward->floor = $request->floor;
        $ward->type  = $request->type;

        if ($ward->save()) {
            return redirect()
                ->route('wards.index')
                ->with('success', 'ward created successfully');
        } else {
            return redirect()
                ->route('wards.create')
                ->with('error', 'ward not created');
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $ward = Ward::findOrFail($id);

        return view('admin.pages.ward.show', compact('ward'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $ward = Ward::findOrFail($id);
        $floors = $this->floorOptions();
        $types = $this->typeOptions();
        // dd($ward);
        return view('admin.pages.ward.edit', compact('ward', 'floors', 'types'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        // dd($request->all());
        $request->validate([
            'name' => "required|min:3|max:100|unique:wards,name,$id",
            'floor' => 'required|max:20',
            'type' => 'required|max:50'
        ]);

        $ward        = Ward::findOrFail($id);
        $ward->name  = $request->name;
        $ward->floor = $request->floor;
        $ward->type  = $request->type;

        if ($ward->save()) {
            return redirect()
                ->route('wards.index')
                ->with('success', 'ward updated successfully');
        } else {
            return redirect()
                ->route('wards.edit', ['ward' => $id])
                ->with('error', 'ward not updated');
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        // dd($id);
        Ward::destroy($id);

        return redirect()
            ->route('wards.index')
            ->with('success', 'ward deleted successfully');
    }

    /**
     * Floor options: values already in the wards table + default floors.
     */
    private function floorOptions()
    {
        return Ward::whereNotNull('floor')->distinct()->pluck('floor')
            ->merge(['1st Floor', '2nd Floor', '3rd Floor', '4th Floor'])
            ->unique()
            ->sort(SORT_NATURAL)
            ->values();
    }

    /**
     * Type options: values already in the wards table + default types.
     */
    private function typeOptions()
    {
        return Ward::whereNotNull('type')->distinct()->pluck('type')
            ->merge(['General', 'Cabin', 'Critical Care', 'Pediatric'])
            ->unique()
            ->sort()
            ->values();
    }
}