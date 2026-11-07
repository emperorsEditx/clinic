@php
    $logo = settings()->logoUrl();
    $zones = collect(timezone_identifiers_list())->mapWithKeys(fn ($z) => [$z => $z]);
@endphp
<x-layouts.app title="Settings">
    <x-page-header title="Clinic settings" subtitle="Branding and defaults used across the application and on every printed document." />

    <form method="POST" action="{{ route('settings.update') }}" enctype="multipart/form-data" class="max-w-5xl space-y-6"
          x-data="{ prefix: @js(old('patient_id_prefix', $s['patient_id_prefix'])), format: @js(old('patient_id_format', $s['patient_id_format'])), pad: {{ (int) old('patient_id_padding', $s['patient_id_padding']) }},
                   get preview() { let f = this.format.includes('{SEQ}') ? this.format : this.format + '-{SEQ}'; return f.replace('{PREFIX}', this.prefix).replace('{YEAR}', '{{ now()->format('Y') }}').replace('{YY}', '{{ now()->format('y') }}').replace('{MONTH}', '{{ now()->format('m') }}').replace('{SEQ}', '1'.padStart(this.pad || 1, '0')); } }">
        @csrf @method('PUT')

        <section class="card">
            <div class="card-header"><h2 class="card-title">Clinic information</h2></div>
            <div class="card-body grid grid-cols-1 gap-5 md:grid-cols-2">
                <x-field name="clinic_name" label="Clinic name" required :value="$s['clinic_name']" />
                <x-field name="clinic_registration_number" label="Registration number" :value="$s['clinic_registration_number']" />
                <x-field class="md:col-span-2" name="clinic_address" label="Address" type="textarea" rows="2" :value="$s['clinic_address']" />
                <x-field name="clinic_phone" label="Contact number" :value="$s['clinic_phone']" />
                <x-field name="clinic_email" label="Email" type="email" :value="$s['clinic_email']" />
                <x-field name="clinic_website" label="Website" type="url" :value="$s['clinic_website']" placeholder="https://" />
                <div>
                    <label class="label" for="clinic_logo">Logo</label>
                    <div class="flex items-center gap-3">
                        @if ($logo)<img src="{{ $logo }}" alt="Current logo" class="size-12 rounded border border-slate-200 object-contain">@endif
                        <input id="clinic_logo" type="file" name="clinic_logo" accept="image/png,image/jpeg" class="block w-full text-[13px] text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-brand-50 file:px-3 file:py-2 file:text-[13px] file:font-medium file:text-brand-700">
                    </div>
                    <p class="help">PNG or JPG, up to 1 MB.</p>
                    @error('clinic_logo')<p class="error">{{ $message }}</p>@enderror
                    @if ($logo)<label class="mt-2 flex items-center gap-2 text-xs text-slate-600"><input type="checkbox" name="remove_logo" value="1" class="rounded border-slate-300"> Remove current logo</label>@endif
                </div>
            </div>
        </section>

        <section class="card">
            <div class="card-header"><h2 class="card-title">Prescription &amp; documents</h2></div>
            <div class="card-body grid grid-cols-1 gap-5 md:grid-cols-2">
                <x-field class="md:col-span-2" name="doctor_info" label="Default doctor information" type="textarea" rows="2" :value="$s['doctor_info']" help="Shown on prescriptions when the prescribing doctor has no qualifications recorded." />
                <x-field name="prescription_header" label="Prescription header text" type="textarea" rows="3" :value="$s['prescription_header']" />
                <x-field name="prescription_footer" label="Prescription footer text" type="textarea" rows="3" :value="$s['prescription_footer']" />
            </div>
        </section>

        <section class="card">
            <div class="card-header"><h2 class="card-title">Patient ID format</h2></div>
            <div class="card-body grid grid-cols-1 gap-5 md:grid-cols-4">
                <x-field name="patient_id_prefix" label="Prefix" required :value="$s['patient_id_prefix']" x-model="prefix" />
                <x-field class="md:col-span-2" name="patient_id_format" label="Format" required :value="$s['patient_id_format']" x-model="format" help="Tokens: {PREFIX} {YEAR} {YY} {MONTH} {SEQ}. The counter resets each year when {YEAR} is used." />
                <x-field name="patient_id_padding" label="Number digits" type="number" min="3" max="10" required :value="$s['patient_id_padding']" x-model.number="pad" />
                <p class="text-[13px] md:col-span-4">Next IDs will look like <span class="rounded bg-slate-100 px-2 py-0.5 font-mono" x-text="preview"></span>. Existing patient IDs never change.</p>
            </div>
        </section>

        <section class="card">
            <div class="card-header"><h2 class="card-title">Appointments</h2></div>
            <div class="card-body grid grid-cols-1 gap-5 md:grid-cols-3">
                <x-field name="appointment_day_start" label="Day starts" type="time" required :value="$s['appointment_day_start']" />
                <x-field name="appointment_day_end" label="Day ends" type="time" required :value="$s['appointment_day_end']" />
                <x-field name="appointment_slot_minutes" label="Slot length" type="select" required :options="collect([5, 10, 15, 20, 30, 45, 60])->mapWithKeys(fn ($m) => [$m => $m.' minutes'])" :value="$s['appointment_slot_minutes']" :empty="false" />
                <x-field class="md:col-span-3" name="appointment_types" label="Appointment types" type="textarea" rows="5" required :value="$s['appointment_types']" help="One per line." />
            </div>
        </section>

        <section class="card">
            <div class="card-header"><h2 class="card-title">Regional</h2></div>
            <div class="card-body grid grid-cols-1 gap-5 md:grid-cols-3">
                <x-field name="timezone" label="Time zone" type="select" required :options="$zones" :value="$s['timezone']" :empty="false" />
                <x-field name="date_format" label="Date format" type="select" required :options="collect(config('clinic.date_formats'))->mapWithKeys(fn ($ex, $f) => [$f => $ex])" :value="$s['date_format']" :empty="false" />
                <x-field name="time_format" label="Time format" type="select" required :options="['h:i A' => '12-hour (02:30 PM)', 'H:i' => '24-hour (14:30)']" :value="$s['time_format']" :empty="false" />
            </div>
        </section>

        <div class="flex justify-end"><button class="btn btn-primary">Save settings</button></div>
    </form>
</x-layouts.app>
