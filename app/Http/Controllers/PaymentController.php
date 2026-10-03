<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PaymentController extends Controller
{
    /* ===================== ADMIN: list / edit / delete ===================== */

    public function index(Request $request)
    {
        $query = Payment::with(['invoice.patient', 'receiver']);

        if ($request->filled('method')) {
            $query->where('payment_method', $request->method);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('q')) {
            $term = trim($request->q);
            $invoiceId = (int) preg_replace('/\D/', '', $term);
            $query->where(function ($q) use ($term, $invoiceId) {
                $q->where('transaction_id', 'like', "%{$term}%");
                if ($invoiceId > 0) {
                    $q->orWhere('invoice_id', $invoiceId);
                }
                $q->orWhereHas('invoice.patient', fn ($p) => $p->where('name', 'like', "%{$term}%"));
            });
        }

        $payments = $query->latest('payment_date')->latest('id')->paginate(15)->withQueryString();

        $success = Payment::where('status', 'Success');
        $totalCollected = (float) (clone $success)->sum('amount');
        $monthCollected = (float) (clone $success)->whereYear('payment_date', now()->year)
            ->whereMonth('payment_date', now()->month)->sum('amount');
        $onlineCount = Payment::where('status', 'Success')->whereNotNull('gateway')->count();
        $pendingCount = Payment::where('status', 'Pending')->count();

        $methods = Payment::query()->distinct()->orderBy('payment_method')->pluck('payment_method');

        return view('admin.pages.payment.index', compact(
            'payments', 'methods', 'totalCollected', 'monthCollected', 'onlineCount', 'pendingCount'
        ));
    }

    public function edit(Payment $payment)
    {
        if ($payment->isGateway()) {
            return redirect()->route('payments.index')
                ->with('error', 'Online (gateway) payments cannot be edited.');
        }

        $payment->load('invoice.patient');

        return view('admin.pages.payment.edit', ['payment' => $payment, 'methods' => Payment::METHODS]);
    }

    public function update(Request $request, Payment $payment)
    {
        if ($payment->isGateway()) {
            return redirect()->route('payments.index')
                ->with('error', 'Online (gateway) payments cannot be edited.');
        }

        $data = $request->validate([
            'amount' => 'required|numeric|min:0.01|max:99999999.99',
            'payment_method' => ['required', 'string', 'max:50'],
            'payment_date' => 'required|date',
        ]);

        DB::transaction(function () use ($payment, $data) {
            $invoice = Invoice::lockForUpdate()->findOrFail($payment->invoice_id);
            $others = (float) $invoice->payments()->where('status', 'Success')
                ->where('id', '!=', $payment->id)->sum('amount');
            $maxAllowed = round((float) $invoice->total_amount - $others, 2);

            if ($data['amount'] > $maxAllowed + 0.001) {
                throw ValidationException::withMessages([
                    'amount' => 'Amount cannot exceed ৳' . number_format($maxAllowed, 2) . ' (invoice total minus other payments).',
                ]);
            }

            $payment->update($data);
            $invoice->syncPayments();
        });

        return redirect()->route('payments.index')->with('success', 'Payment updated.');
    }

    public function destroy(Payment $payment)
    {
        DB::transaction(function () use ($payment) {
            $invoice = Invoice::lockForUpdate()->findOrFail($payment->invoice_id);
            $payment->delete();
            $invoice->syncPayments();
        });

        return redirect()->back()->with('success', 'Payment deleted and invoice recalculated.');
    }

    /* ===================== ADMIN + RECEPTIONIST: take payment ===================== */

    /** Counter payment (cash / bKash / Nagad / card ...) against an invoice. */
    public function store(Request $request, Invoice $invoice)
    {
        if ($invoice->status === 'Cancelled') {
            return back()->with('error', 'Cannot take payment on a cancelled invoice.');
        }

        $data = $request->validate([
            'amount' => 'required|numeric|min:0.01|max:99999999.99',
            'payment_method' => ['required', Rule::in(Payment::METHODS)],
            'payment_date' => 'required|date',
        ]);

        DB::transaction(function () use ($invoice, $data) {
            $locked = Invoice::lockForUpdate()->findOrFail($invoice->id);
            $due = round((float) $locked->total_amount - (float) $locked->paid_amount, 2);

            if ($data['amount'] > $due + 0.001) {
                throw ValidationException::withMessages([
                    'amount' => 'Amount cannot exceed the due ৳' . number_format($due, 2) . '.',
                ]);
            }

            $locked->payments()->create($data + [
                'status' => 'Success',
                'received_by' => auth()->id(),
            ]);

            $locked->syncPayments();
        });

        return redirect()->route('invoices.show', $invoice->id)->with('success', 'Payment received.');
    }
}
