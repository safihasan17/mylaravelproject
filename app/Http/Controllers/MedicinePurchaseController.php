<?php

namespace App\Http\Controllers;

use App\Models\Medicine;
use App\Models\MedicinePurchase;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MedicinePurchaseController extends Controller
{
    public function index(Request $request)
    {
        $query = MedicinePurchase::with(['supplier', 'medicine']);

        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        if ($request->filled('q')) {
            $term = $request->q;
            $query->whereHas('medicine', fn ($q) => $q->where('name', 'like', "%{$term}%"));
        }

        $purchases = $query->latest('purchase_date')->latest('id')->paginate(15)->withQueryString();
        $suppliers = Supplier::orderBy('name')->get();

        $totalPurchases = MedicinePurchase::count();
        $totalUnits = (int) MedicinePurchase::sum('quantity');
        $totalSpent = (float) MedicinePurchase::sum(DB::raw('quantity * purchase_price'));
        $monthSpent = (float) MedicinePurchase::whereYear('purchase_date', now()->year)
            ->whereMonth('purchase_date', now()->month)
            ->sum(DB::raw('quantity * purchase_price'));

        return view('admin.pages.medicine-purchase.index', compact(
            'purchases', 'suppliers', 'totalPurchases', 'totalUnits', 'totalSpent', 'monthSpent'
        ));
    }

    public function create()
    {
        return view('admin.pages.medicine-purchase.create', [
            'suppliers' => Supplier::orderBy('name')->get(),
            'medicines' => Medicine::orderBy('name')->get(),
        ]);
    }

    /** Save the purchase AND add the quantity to the medicine's stock. */
    public function store(Request $request)
    {
        $data = $this->validated($request);

        DB::transaction(function () use ($data) {
            MedicinePurchase::create($data);
            Medicine::where('id', $data['medicine_id'])->increment('stock_quantity', $data['quantity']);
        });

        return redirect()->route('medicine-purchases.index')
            ->with('success', 'Purchase recorded and stock updated.');
    }

    public function show(MedicinePurchase $medicinePurchase)
    {
        $medicinePurchase->load(['supplier', 'medicine']);

        return view('admin.pages.medicine-purchase.show', ['purchase' => $medicinePurchase]);
    }

    public function edit(MedicinePurchase $medicinePurchase)
    {
        return view('admin.pages.medicine-purchase.edit', [
            'purchase' => $medicinePurchase,
            'suppliers' => Supplier::orderBy('name')->get(),
            'medicines' => Medicine::orderBy('name')->get(),
        ]);
    }

    /** Keep stock in sync when quantity / medicine changes. */
    public function update(Request $request, MedicinePurchase $medicinePurchase)
    {
        $data = $this->validated($request);

        DB::transaction(function () use ($data, $medicinePurchase) {
            $purchase = MedicinePurchase::lockForUpdate()->findOrFail($medicinePurchase->id);
            $oldMedicine = Medicine::lockForUpdate()->findOrFail($purchase->medicine_id);

            if ((int) $data['medicine_id'] === (int) $purchase->medicine_id) {
                $delta = $data['quantity'] - $purchase->quantity;

                if ($delta < 0 && $oldMedicine->stock_quantity < abs($delta)) {
                    throw ValidationException::withMessages([
                        'quantity' => 'Cannot reduce quantity: only ' . $oldMedicine->stock_quantity . ' in stock (some of it was already used).',
                    ]);
                }

                $oldMedicine->increment('stock_quantity', $delta);
            } else {
                if ($oldMedicine->stock_quantity < $purchase->quantity) {
                    throw ValidationException::withMessages([
                        'medicine_id' => 'Cannot change medicine: the old medicine no longer has enough stock to reverse this purchase.',
                    ]);
                }

                $oldMedicine->decrement('stock_quantity', $purchase->quantity);
                Medicine::where('id', $data['medicine_id'])->increment('stock_quantity', $data['quantity']);
            }

            $purchase->update($data);
        });

        return redirect()->route('medicine-purchases.index')
            ->with('success', 'Purchase updated and stock adjusted.');
    }

    /** Deleting a purchase removes its quantity from stock. */
    public function destroy(MedicinePurchase $medicinePurchase)
    {
        try {
            DB::transaction(function () use ($medicinePurchase) {
                $purchase = MedicinePurchase::lockForUpdate()->findOrFail($medicinePurchase->id);
                $medicine = Medicine::lockForUpdate()->findOrFail($purchase->medicine_id);

                if ($medicine->stock_quantity < $purchase->quantity) {
                    throw new \RuntimeException('Cannot delete: current stock is lower than this purchase quantity (some was already used).');
                }

                $medicine->decrement('stock_quantity', $purchase->quantity);
                $purchase->delete();
            });
        } catch (\RuntimeException $e) {
            return redirect()->route('medicine-purchases.index')->with('error', $e->getMessage());
        }

        return redirect()->route('medicine-purchases.index')
            ->with('success', 'Purchase deleted and stock adjusted.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'medicine_id' => 'required|exists:medicines,id',
            'quantity' => 'required|integer|min:1|max:1000000',
            'purchase_price' => 'required|numeric|min:0|max:99999999.99',
            'purchase_date' => 'required|date',
        ]);
    }
}
