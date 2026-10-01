@extends('admin.layouts.master')

@section('title', 'Invoice Details')

@section('content')
    <main id="main-container">

        <div class="content" id="inv-print-area">
            <x-admin.phead title="Invoice INV-{{ str_pad($invoice->id, 4, '0', STR_PAD_LEFT) }}"
                subtitle="{{ $invoice->patient->name ?? 'N/A' }} &mdash; {{ $invoice->invoice_date?->format('d M Y') }}">

                <a href="{{ route('invoices.index') }}" type="button" class="btn btn-sm btn-secondary no-print">
                    <i class="fa fa-arrow-left opacity-50 me-1"></i> Back to Invoices
                </a>

                <a href="{{ route('invoices.edit', $invoice->id) }}" type="button" class="btn btn-sm btn-primary no-print">
                    <i class="fa fa-pencil-alt opacity-50 me-1"></i> Edit
                </a>

                <button type="button" class="btn btn-sm btn-alt-secondary no-print" onclick="window.print()">
                    <i class="fa fa-print opacity-50 me-1"></i> Print
                </button>

            </x-admin.phead>

            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show no-print" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="close"></button>
                </div>
            @endif

            @php
                $statusColors = [
                    'Unpaid' => 'danger',
                    'Partially Paid' => 'warning',
                    'Paid' => 'success',
                    'Cancelled' => 'secondary',
                ];
                $statusColor = $statusColors[$invoice->status] ?? 'secondary';
                $dueAmount = $invoice->total_amount - $invoice->paid_amount;

                $referredDoctor = $invoice->admission->doctor->user->name ?? $invoice->appointment->doctor->user->name ?? null;
                $patientAge = $invoice->patient->dob ? $invoice->patient->dob->age : null;
            @endphp

            <div class="block block-rounded">
                <div class="block-content block-content-full" style="max-width: 820px; margin: 0 auto;">

                    {{-- Letterhead — printable only --}}
                    <div class="d-none d-print-block text-center mb-4">
                        <h3 class="mb-0">{{ config('app.name', 'Hospital Management') }}</h3>
                        <p class="text-muted mb-0">House/Address line, City &mdash; Phone: 01XXXXXXXXX</p>
                        <hr>
                    </div>

                    <div class="d-flex justify-content-between align-items-start mb-4">
                        <h4 class="mb-0">Invoice</h4>
                        <div class="text-end">
                            <div class="fw-semibold">Invoice No: INV-{{ str_pad($invoice->id, 4, '0', STR_PAD_LEFT) }}</div>
                            <div class="fs-sm text-muted">Date: {{ $invoice->invoice_date?->format('d M Y') }}</div>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <table class="table table-borderless table-sm mb-0">
                                <tbody>
                                    <tr>
                                        <td class="fw-semibold" style="width: 110px;">Name</td>
                                        <td>: {{ $invoice->patient->name ?? 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-semibold">Age</td>
                                        <td>: {{ $patientAge ? $patientAge . ' year(s)' : '-' }}</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-semibold">Gender</td>
                                        <td>: {{ $invoice->patient->gender ?? '-' }}</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-semibold">Phone</td>
                                        <td>: {{ $invoice->patient->phone ?? '-' }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless table-sm mb-0">
                                <tbody>
                                    <tr>
                                        <td class="fw-semibold" style="width: 110px;">Ref. by</td>
                                        <td>: {{ $referredDoctor ? 'Dr. ' . $referredDoctor : 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-semibold">Type</td>
                                        <td>:
                                            @if ($invoice->admission_id)
                                                IPD (Admission #{{ $invoice->admission_id }})
                                            @elseif ($invoice->appointment_id)
                                                OPD (Appointment #{{ $invoice->appointment_id }})
                                            @else
                                                &mdash;
                                            @endif
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-sm">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 50px;">SL</th>
                                    <th>Description</th>
                                    <th class="text-center" style="width: 100px;">Type</th>
                                    <th class="text-end" style="width: 140px;">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($invoice->items as $i => $item)
                                    <tr>
                                        <td>{{ $i + 1 }}</td>
                                        <td>{{ $item->description }}</td>
                                        <td class="text-center">
                                            <span class="badge bg-secondary">{{ $item->item_type }}</span>
                                        </td>
                                        <td class="text-end">৳{{ number_format($item->amount, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted">No items added to this invoice
                                            yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="row">
                        <div class="col-md-6"></div>
                        <div class="col-md-6">
                            <table class="table table-borderless table-sm mb-0">
                                <tbody>
                                    <tr>
                                        <td class="fw-semibold">Total Amount</td>
                                        <td class="text-end">৳{{ number_format($invoice->total_amount, 2) }}</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-semibold">Received</td>
                                        <td class="text-end">৳{{ number_format($invoice->paid_amount, 2) }}</td>
                                    </tr>
                                    <tr class="border-top">
                                        <td class="fw-bold">Due</td>
                                        <td class="text-end fw-bold text-danger">৳{{ number_format($dueAmount, 2) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-end mt-5 pt-5">
                        <div>
                            @if ($invoice->status === 'Paid')
                                <span class="badge bg-success fs-5 px-4 py-2 border border-success">PAID</span>
                            @else
                                <span class="badge bg-{{ $statusColor }} fs-6 px-3 py-2">{{ $invoice->status }}</span>
                            @endif
                        </div>
                        <div class="text-center">
                            <p class="mb-0">____________________________</p>
                            <p class="mb-0 fs-sm">Authorized Signature</p>
                        </div>
                    </div>

                    <div class="d-none d-print-block text-center fs-sm text-muted mt-4">
                        <hr>
                        <p class="mb-0">Thank you for choosing {{ config('app.name', 'our hospital') }}.</p>
                    </div>

                </div>
            </div>

        </div>
        <!-- END Page Content -->
    </main>

    <style>
        @media print {
            #page-header, #sidebar, #page-footer, .no-print {
                display: none !important;
            }
            #page-container, #main-container, #inv-print-area {
                margin: 0 !important;
                padding: 0 !important;
                width: 100% !important;
            }
            .block {
                box-shadow: none !important;
                border: none !important;
            }
        }
    </style>

@endsection