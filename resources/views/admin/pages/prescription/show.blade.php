@extends('admin.layouts.master')

@section('title', 'Prescription Details')

@section('content')
    <main id="main-container">

        <div class="content">

            {{-- Action bar (screen only) --}}
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 no-print">
                <h1 class="h4 mb-0">
                    Prescription
                    <span class="text-muted fs-6">RX-{{ str_pad($prescription->id, 4, '0', STR_PAD_LEFT) }}</span>
                </h1>

                <div class="d-flex gap-2">
                    <a href="{{ route('prescriptions.index') }}" class="btn btn-sm btn-secondary">
                        <i class="fa fa-arrow-left opacity-50 me-1"></i> Back
                    </a>
                    <a href="{{ route('prescriptions.edit', $prescription->id) }}" class="btn btn-sm btn-primary">
                        <i class="fa fa-pencil-alt opacity-50 me-1"></i> Edit
                    </a>
                    <button type="button" class="btn btn-sm btn-alt-secondary" onclick="window.print()">
                        <i class="fa fa-print opacity-50 me-1"></i> Print
                    </button>
                </div>
            </div>

            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show no-print" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="close"></button>
                </div>
            @endif

            {{-- ============ PRINTABLE PRESCRIPTION SHEET ============ --}}
            <div id="rx-print-area" class="rx-sheet">

                {{-- Header: Rx mark + doctor / clinic block --}}
                <header class="rx-head">
                    <div class="rx-symbol">R<span>x</span></div>

                    <div class="rx-clinic">
                        <div class="rx-doc-name">{{ $prescription->doctor->user->name ?? 'N/A' }}</div>
                        <div class="rx-doc-qual">{{ $prescription->doctor->qualification ?? '' }}</div>
                        <div class="rx-doc-spec">{{ $prescription->doctor->specialization ?? '' }}</div>

                        @if ($prescription->doctor->registration_no ?? false)
                            <div class="rx-meta"><span class="rx-meta-k">Registration No:</span>
                                {{ $prescription->doctor->registration_no }}</div>
                        @endif

                        @if ($prescription->doctor->user->email ?? false)
                            <div class="rx-meta"><span class="rx-meta-k">Email:</span>
                                {{ $prescription->doctor->user->email }}</div>
                        @endif

                        <div class="rx-meta">{{ config('app.name', 'Hospital Management') }}
                            @if ($prescription->doctor->department->name ?? false)
                                ({{ $prescription->doctor->department->name }})
                            @endif
                        </div>

                        @if ($prescription->doctor->address ?? false)
                            <div class="rx-meta">{{ $prescription->doctor->address }}</div>
                        @endif
                    </div>
                </header>

                <div class="rx-rule"></div>

                {{-- Patient details + reference --}}
                <section class="rx-patient">
                    <div class="rx-col">
                        <div class="rx-label">Patient Details</div>
                        <div class="rx-strong">{{ $prescription->patient->name ?? 'N/A' }}</div>
                        <div class="rx-strong">
                            @php
                                $age = $prescription->patient->age ?? null;
                                $dob = $prescription->patient->date_of_birth ?? null;
                                if (!$age && $dob) {
                                    $age = \Carbon\Carbon::parse($dob)->age;
                                }
                            @endphp
                            {{ $age ? $age . ' yrs' : '' }}{{ $age && ($prescription->patient->gender ?? null) ? ', ' : '' }}{{ ucfirst($prescription->patient->gender ?? '') }}
                        </div>
                        <div class="rx-label">Address: <span
                                class="rx-val">{{ $prescription->patient->address ?? '' }}</span></div>
                        <div class="rx-label">Ph No.: <span
                                class="rx-val">{{ $prescription->patient->phone ?? '' }}</span></div>
                    </div>

                    <div class="rx-col">
                        <div class="rx-kv">
                            <span class="rx-label">Ref no:</span>
                            <span class="rx-strong">RX-{{ str_pad($prescription->id, 4, '0', STR_PAD_LEFT) }}</span>
                        </div>
                        <div class="rx-kv">
                            <span class="rx-label">Date &amp; Time:</span>
                            <span class="rx-strong">
                                {{ $prescription->prescription_date?->format('d M Y') ?? '-' }}
                                {{ $prescription->created_at?->format('H:i:s') }}
                            </span>
                        </div>
                        @if ($prescription->appointment)
                            <div class="rx-kv">
                                <span class="rx-label">Appointment:</span>
                                <span class="rx-strong">#{{ $prescription->appointment->id }} &mdash;
                                    {{ $prescription->appointment->appointment_date?->format('d M Y') }}</span>
                            </div>
                        @endif
                    </div>
                </section>

                {{-- Symptoms & diagnosis --}}
                <section class="rx-clinical">
                    <div class="rx-label">Symptoms(HOPI):</div>
                    <div class="rx-strong">{{ $prescription->symptoms ?? '—' }}</div>
                    <div class="rx-label">Provisional Diagnosis:</div>
                    <div class="rx-strong">{{ $prescription->diagnosis ?? '—' }}</div>
                </section>

                {{-- Lab tests + medicines --}}
                <table class="rx-table">
                    <thead>
                        <tr>
                            <th class="rx-th-tests">Lab Tests</th>
                            <th class="rx-th-meds">Medicines</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="rx-td-tests">
                                @php $tests = $prescription->labTests ?? collect(); @endphp
                                @forelse ($tests as $test)
                                    <div class="rx-test">{{ $loop->iteration }}. {{ $test->name ?? $test->test_name ?? '-' }}
                                    </div>
                                @empty
                                    <div class="rx-test">No tests prescribed.</div>
                                @endforelse
                            </td>

                            <td class="rx-td-meds">
                                @forelse ($prescription->prescriptionMedicines as $row)
                                    <div class="rx-med">
                                        <div class="rx-med-no">{{ $loop->iteration }}.</div>
                                        <div class="rx-med-name">
                                            <div class="rx-strong">{{ $row->medicine->name ?? 'N/A' }}</div>
                                            @if ($row->medicine->generic_name ?? false)
                                                <div class="rx-generic">{{ $row->medicine->generic_name }}</div>
                                            @endif
                                            @if ($row->medicine->type ?? false)
                                                <div class="rx-generic rx-italic">{{ $row->medicine->type }}</div>
                                            @endif
                                        </div>
                                        <div class="rx-med-dose">
                                            <div>{{ $row->dosage ?? '-' }}</div>
                                            <div>{{ $row->duration ?? '-' }}</div>
                                            <div>{{ $row->instructions ?? '-' }}</div>
                                        </div>
                                    </div>
                                @empty
                                    <div class="rx-med-empty">No medicines added.</div>
                                @endforelse

                                <div class="rx-note">***** <strong>Note:</strong> Substitution allowed wherever applicable.
                                    *****</div>

                                <div class="rx-label mt-2">General Instructions:</div>
                                @if ($prescription->notes)
                                    <div class="rx-notes-body">{{ $prescription->notes }}</div>
                                @endif

                                <div class="rx-label">Next Appointment:
                                    <span class="rx-val">
                                        {{ $prescription->appointment?->appointment_date?->format('d M Y') ?? '' }}
                                    </span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>

                {{-- Signature --}}
                <section class="rx-sign">
                    <div class="rx-sign-line"></div>
                    <div class="rx-strong">{{ $prescription->doctor->user->name ?? '' }}</div>
                    <div>{{ $prescription->doctor->qualification ?? '' }}</div>
                    <div>{{ $prescription->doctor->specialization ?? '' }}</div>
                </section>

                {{-- Disclaimer --}}
                <footer class="rx-disclaimer">
                    <hr>
                </footer>

                <div class="rx-issued no-print">
                    Issued {{ $prescription->created_at?->format('d M Y, h:i A') ?? '-' }}
                    &middot; Updated {{ $prescription->updated_at?->format('d M Y, h:i A') ?? '-' }}
                </div>

            </div>
        </div>
    </main>

    <style>
       
        .rx-sheet {
            background: #fff;
            color: #111;
            max-width: 210mm;
            margin: 0 auto;
            padding: 12mm 10mm;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 12px;
            line-height: 1.35;
            box-shadow: 0 0 0 1px #e5e5e5;
        }

        .rx-head {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 16px;
        }

        .rx-symbol {
            font-family: Georgia, 'Times New Roman', serif;
            font-size: 52px;
            font-weight: 700;
            line-height: 1;
            letter-spacing: -2px;
        }

        .rx-symbol span {
            display: inline-block;
            transform: translateY(6px);
        }

        .rx-clinic {
            text-align: right;
        }

        .rx-doc-name {
            font-size: 15px;
            font-weight: 600;
        }

        .rx-doc-qual,
        .rx-doc-spec {
            font-size: 13px;
        }

        .rx-meta {
            font-size: 10px;
            color: #777;
        }

        .rx-meta-k {
            color: #999;
        }

        .rx-rule {
            border-top: 1.5px solid #333;
            margin: 8px 0;
        }

        .rx-patient {
            display: flex;
            gap: 24px;
        }

        .rx-patient .rx-col {
            flex: 1;
        }

        .rx-label {
            color: #7a7a7a;
        }

        .rx-val,
        .rx-strong {
            color: #111;
            font-weight: 500;
        }

        .rx-kv {
            display: flex;
            gap: 8px;
        }

        .rx-clinical {
            margin-top: 8px;
        }

        .rx-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            table-layout: fixed;
        }

        .rx-table th,
        .rx-table td {
            border: 1px solid #333;
            vertical-align: top;
            padding: 6px 8px;
        }

        .rx-table thead th {
            text-align: center;
            font-weight: 400;
            border-left: 0;
            border-right: 0;
        }

        .rx-th-tests {
            width: 30%;
        }

        .rx-table tbody td {
            border: 0;
            border-right: 1px solid #333;
            height: 340px;
        }

        .rx-table tbody td:last-child {
            border-right: 0;
        }

        .rx-med {
            display: flex;
            gap: 6px;
            margin-bottom: 8px;
        }

        .rx-med-no {
            width: 16px;
        }

        .rx-med-name {
            flex: 0 0 42%;
        }

        .rx-med-dose {
            flex: 1;
            font-size: 11px;
            color: #333;
        }

        .rx-generic {
            color: #8a8a8a;
        }

        .rx-italic {
            font-style: italic;
        }

        .rx-note {
            text-align: center;
            font-size: 11px;
            margin: 10px 0;
        }

        .rx-notes-body {
            white-space: pre-line;
            font-size: 11px;
        }

        .rx-med-empty {
            color: #888;
        }

        .rx-sign {
            text-align: right;
            margin-top: 14px;
        }

        .rx-sign-line {
            width: 180px;
            border-top: 1px solid #333;
            margin-left: auto;
            margin-bottom: 4px;
        }

        .rx-disclaimer {
            margin-top: 14px;
            border-top: 1.5px solid #333;
            padding-top: 6px;
            font-size: 9px;
            color: #555;
        }

        .rx-disclaimer ol {
            margin: 4px 0 0 18px;
            padding: 0;
        }

        .rx-issued {
            margin-top: 10px;
            font-size: 11px;
            color: #888;
        }

        /* ===== Print: force a single A4 page ===== */
        @media print {
            @page {
                size: A4 portrait;
                margin: 8mm;
            }

            #page-header,
            #sidebar,
            #page-footer,
            #page-loader,
            .no-print {
                display: none !important;
            }

            html,
            body,
            #page-container,
            #main-container,
            .content {
                margin: 0 !important;
                padding: 0 !important;
                width: 100% !important;
                background: #fff !important;
            }

            .rx-sheet {
                max-width: none;
                width: 100%;
                margin: 0;
                padding: 0;
                box-shadow: none;
                font-size: 10.5px;
                page-break-inside: avoid;
                break-inside: avoid;
            }

            .rx-sheet * {
                page-break-inside: avoid;
                break-inside: avoid;
            }

            .rx-table tbody td {
                height: auto;
                min-height: 120mm;
            }

            .rx-symbol {
                font-size: 44px;
            }
        }
    </style>
@endsection