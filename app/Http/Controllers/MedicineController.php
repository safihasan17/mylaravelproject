<?php

namespace App\Http\Controllers;

use App\Models\Medicine;
use Illuminate\Http\Request;

class MedicineController extends Controller
{
    public function index(Request $request)
    {
        $low = Medicine::LOW_STOCK_THRESHOLD;

        $query = Medicine::query();

        if ($request->filled('q')) {
            $term = $request->q;
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('generic_name', 'like', "%{$term}%");
            });
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->stock === 'low') {
            $query->where('stock_quantity', '>', 0)->where('stock_quantity', '<', $low);
        } elseif ($request->stock === 'out') {
            $query->where('stock_quantity', '<=', 0);
        } elseif ($request->stock === 'in') {
            $query->where('stock_quantity', '>=', $low);
        }

        $medicines = $query->orderBy('name')->paginate(15)->withQueryString();

        $categories = Medicine::whereNotNull('category')->distinct()->orderBy('category')->pluck('category');

        $totalMedicines = Medicine::count();
        $inStockCount = Medicine::where('stock_quantity', '>=', $low)->count();
        $lowStockCount = Medicine::where('stock_quantity', '>', 0)->where('stock_quantity', '<', $low)->count();
        $outOfStockCount = Medicine::where('stock_quantity', '<=', 0)->count();

        return view('admin.pages.medicine.index', compact(
            'medicines', 'categories', 'totalMedicines', 'inStockCount', 'lowStockCount', 'outOfStockCount', 'low'
        ));
    }

    public function create()
    {
        return view('admin.pages.medicine.create');
    }

    public function store(Request $request)
    {
        Medicine::create($this->validated($request));

        return redirect()->route('medicines.index')->with('success', 'Medicine added successfully.');
    }

    public function show(Medicine $medicine)
    {
        $purchases = $medicine->purchases()->with('supplier')->latest('purchase_date')->latest('id')->take(10)->get();

        return view('admin.pages.medicine.show', compact('medicine', 'purchases'));
    }

    public function edit(Medicine $medicine)
    {
        return view('admin.pages.medicine.edit', compact('medicine'));
    }

    public function update(Request $request, Medicine $medicine)
    {
        $medicine->update($this->validated($request));

        return redirect()->route('medicines.index')->with('success', 'Medicine updated successfully.');
    }

    public function destroy(Medicine $medicine)
    {
        if ($medicine->purchases()->exists() || $medicine->prescriptionMedicines()->exists()) {
            return redirect()->route('medicines.index')
                ->with('error', 'This medicine is used in purchases or prescriptions and cannot be deleted.');
        }

        $medicine->delete();

        return redirect()->route('medicines.index')->with('success', 'Medicine deleted successfully.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:150',
            'generic_name' => 'nullable|string|max:150',
            'category' => 'nullable|string|max:100',
            'unit_price' => 'nullable|numeric|min:0|max:99999999.99',
            'stock_quantity' => 'required|integer|min:0',
        ]);
    }
}
