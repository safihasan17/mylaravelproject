<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        $query = Supplier::withCount('purchases');

        if ($request->filled('q')) {
            $term = $request->q;
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")->orWhere('contact', 'like', "%{$term}%");
            });
        }

        $suppliers = $query->orderBy('name')->paginate(15)->withQueryString();
        $totalSuppliers = Supplier::count();

        return view('admin.pages.supplier.index', compact('suppliers', 'totalSuppliers'));
    }

    public function create()
    {
        return view('admin.pages.supplier.create');
    }

    public function store(Request $request)
    {
        Supplier::create($this->validated($request));

        return redirect()->route('suppliers.index')->with('success', 'Supplier added successfully.');
    }

    public function show(Supplier $supplier)
    {
        $purchases = $supplier->purchases()->with('medicine')->latest('purchase_date')->latest('id')->take(10)->get();

        return view('admin.pages.supplier.show', compact('supplier', 'purchases'));
    }

    public function edit(Supplier $supplier)
    {
        return view('admin.pages.supplier.edit', compact('supplier'));
    }

    public function update(Request $request, Supplier $supplier)
    {
        $supplier->update($this->validated($request));

        return redirect()->route('suppliers.index')->with('success', 'Supplier updated successfully.');
    }

    public function destroy(Supplier $supplier)
    {
        if ($supplier->purchases()->exists()) {
            return redirect()->route('suppliers.index')
                ->with('error', 'This supplier has purchase records and cannot be deleted.');
        }

        $supplier->delete();

        return redirect()->route('suppliers.index')->with('success', 'Supplier deleted successfully.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:100',
            'contact' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:1000',
        ]);
    }
}
