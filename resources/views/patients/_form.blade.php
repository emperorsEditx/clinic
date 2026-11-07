@php $dupes = session('duplicates'); @endphp
@if ($dupes)
    <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 p-4 text-[13px] text-amber-900" role="alert">
        <p class="flex items-center gap-2 font-semibold"><x-icon name="warning" /> Possible duplicate registration</p>
        <p class="mt-1">The following patients have the same name and phone number or date of birth:</p>
        <ul class="mt-2 space-y-1">@foreach ($dupes as $d)<li><a href="{{ $d['url'] }}" class="font-medium underline">{{ $d['code'] }} — {{ $d['name'] }}</a> {{ $d['phone'] ? '· '.$d['phone'] : '' }}</li>@endforeach</ul>
        <label class="mt-3 flex items-center gap-2 font-medium"><input type="checkbox" name="confirm_duplicate" value="1" class="rounded border-amber-400"> This is a different person — register anyway</label>
    </div>
@endif
<div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
    <div class="card lg:col-span-2">
        <div class="card-header"><h2 class="card-title">Personal details</h2></div>
        <div class="card-body grid grid-cols-1 gap-4 sm:grid-cols-2">
            <x-field name="full_name" label="Full name" :value="$patient->full_name" required class="sm:col-span-2" autocomplete="off" />
            <x-field name="gender" label="Gender" type="select" :options="App\Enums\Gender::options()" :value="$patient->gender" required />
            <div class="grid grid-cols-2 gap-3">
                <x-field name="age" label="Age (years)" type="number" min="0" max="130" :value="$patient->age" help="Or enter date of birth" />
                <x-field name="date_of_birth" label="Date of birth" type="date" :value="$patient->date_of_birth" max="{{ today()->toDateString() }}" />
            </div>
            <x-field name="phone" label="Phone number" type="tel" :value="$patient->phone" />
            <x-field name="email" label="Email" type="email" :value="$patient->email" />
            <x-field name="address" label="Address" type="textarea" rows="2" :value="$patient->address" class="sm:col-span-2" />
        </div>
    </div>
    <div class="space-y-6">
        <div class="card">
            <div class="card-header"><h2 class="card-title">Registration</h2></div>
            <div class="card-body space-y-4">
                @if ($patient->exists)
                    <div><p class="label">Patient ID</p><p class="font-mono text-sm">{{ $patient->patient_code }}</p><p class="help">Permanent — cannot be changed.</p></div>
                @else
                    <div class="rounded-lg bg-slate-50 px-3 py-2 text-xs text-slate-600">A unique Patient ID (e.g. {{ str_replace(['{PREFIX}', '{YEAR}', '{YY}', '{MONTH}', '{SEQ}'], [settings('patient_id_prefix'), now()->year, now()->format('y'), now()->format('m'), str_pad('1', (int) settings('patient_id_padding'), '0', STR_PAD_LEFT)], settings('patient_id_format')) }}) is generated on save.</div>
                @endif
                <x-field name="registration_date" label="Registration date" type="date" :value="$patient->registration_date ?? today()" max="{{ today()->toDateString() }}" />
                <x-field name="blood_group" label="Blood group" type="select" :options="array_combine(config('clinic.blood_groups'), config('clinic.blood_groups'))" :value="$patient->blood_group" empty="Unknown" />
            </div>
        </div>
        <div class="card">
            <div class="card-header"><h2 class="card-title">Emergency contact</h2></div>
            <div class="card-body space-y-4">
                <x-field name="emergency_contact_name" label="Name" :value="$patient->emergency_contact_name" />
                <div class="grid grid-cols-2 gap-3">
                    <x-field name="emergency_contact_phone" label="Phone" type="tel" :value="$patient->emergency_contact_phone" />
                    <x-field name="emergency_contact_relation" label="Relation" :value="$patient->emergency_contact_relation" />
                </div>
            </div>
        </div>
    </div>
    <div class="card lg:col-span-3">
        <div class="card-body"><x-field name="notes" label="Additional notes" type="textarea" rows="3" :value="$patient->notes" help="Administrative notes. Record clinical history in the Medical History tab." /></div>
    </div>
</div>
