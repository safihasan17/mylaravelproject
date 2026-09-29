
@php
    $existingAdmission = isset($prescription) ? $prescription->admission : null;
    $admitChoice = old('admit_patient', '0');
    $statusColors = ['Admitted' => 'danger', 'Discharged' => 'success', 'Transferred' => 'info'];
@endphp

<div class="mb-4">
    <p class="fw-semibold mb-2">Admission</p>

    @if ($existingAdmission)
        {{-- Already admitted from this prescription: read-only summary --}}
        <div class="alert alert-info mb-0">
            <span class="badge bg-{{ $statusColors[$existingAdmission->status] ?? 'secondary' }} me-2">
                {{ $existingAdmission->status }}
            </span>
            Ward: <strong>{{ $existingAdmission->ward->name ?? '-' }}</strong>,
            Bed: <strong>{{ $existingAdmission->bed->bed_number ?? '-' }}</strong>
            &middot; {{ $existingAdmission->admission_date?->format('d M Y, h:i A') }}
            <a href="{{ route('admissions.show', $existingAdmission->id) }}" class="ms-2">View admission</a>
        </div>
    @else
        <div class="mb-3">
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="admit_patient" id="admit-no" value="0"
                    @checked($admitChoice !== '1')>
                <label class="form-check-label" for="admit-no">No admission (tests / treatment, then go home)</label>
            </div>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="admit_patient" id="admit-yes" value="1"
                    @checked($admitChoice === '1')>
                <label class="form-check-label" for="admit-yes">Admit patient</label>
            </div>
            <x-admin.error-msg name="admit_patient" />
        </div>

        <div id="admission-fields" class="{{ $admitChoice === '1' ? '' : 'd-none' }}">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Ward</label>
                    <select class="form-select" name="admission[ward_id]" id="adm-ward-select">
                        <option value="" selected disabled>Select Ward</option>
                        @foreach ($wards as $ward)
                            <option value="{{ $ward->id }}" @selected(old('admission.ward_id') == $ward->id)>
                                {{ $ward->name }}</option>
                        @endforeach
                    </select>
                    <x-admin.error-msg name="admission.ward_id" />
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Bed <span class="text-muted fs-sm">(available only)</span></label>
                    <select class="form-select" name="admission[bed_id]" id="adm-bed-select">
                        <option value="" selected disabled>Select Ward first</option>
                        @foreach ($beds as $bed)
                            <option value="{{ $bed->id }}" data-ward="{{ $bed->ward_id }}"
                                @selected(old('admission.bed_id') == $bed->id)>{{ $bed->bed_number }}</option>
                        @endforeach
                    </select>
                    <x-admin.error-msg name="admission.bed_id" />
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Admission Date &amp; Time</label>
                    <input type="datetime-local" class="form-control" name="admission[admission_date]"
                        value="{{ old('admission.admission_date', now()->format('Y-m-d\TH:i')) }}">
                    <x-admin.error-msg name="admission.admission_date" />
                </div>
            </div>
        </div>

        <script>
            (function () {
                const box = document.getElementById('admission-fields');
                const yes = document.getElementById('admit-yes');
                const no = document.getElementById('admit-no');
                const wardSelect = document.getElementById('adm-ward-select');
                const bedSelect = document.getElementById('adm-bed-select');
                const allBedOptions = Array.from(bedSelect.options).filter(o => o.value !== '');

                function filterBeds() {
                    const wardId = wardSelect.value;
                    const current = bedSelect.value;
                    bedSelect.innerHTML = '';

                    const ph = document.createElement('option');
                    ph.value = '';
                    ph.disabled = true;
                    ph.selected = true;
                    ph.textContent = wardId ? 'Select Bed' : 'Select Ward first';
                    bedSelect.appendChild(ph);

                    allBedOptions.filter(o => o.dataset.ward === wardId).forEach(o => {
                        const clone = o.cloneNode(true);
                        if (clone.value === current) { clone.selected = true; ph.selected = false; }
                        bedSelect.appendChild(clone);
                    });
                }

                // Show fields only when "Admit patient" is chosen.
                // Hidden fields are disabled so nothing is sent -> admission_id stays NULL.
                function toggle() {
                    const on = yes.checked;
                    box.classList.toggle('d-none', !on);
                    box.querySelectorAll('select, input').forEach(el => el.disabled = !on);
                }

                wardSelect.addEventListener('change', filterBeds);
                yes.addEventListener('change', toggle);
                no.addEventListener('change', toggle);

                if (wardSelect.value) filterBeds();
                toggle();
            })();
        </script>
    @endif
</div>
