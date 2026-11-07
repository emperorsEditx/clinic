@php $editing = $appointment->exists; @endphp
<div class="card">
    <div class="card-body grid grid-cols-1 gap-5 md:grid-cols-2">
        <div class="md:col-span-2">
            @if ($editing)
                <p class="label">Patient</p>
                <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2"><p class="text-sm font-medium">{{ $patient->full_name }}</p><p class="text-xs text-slate-500">{{ $patient->patient_code }}</p></div>
            @else
                <x-patient-picker :patient="$patient" />
            @endif
        </div>
        <x-field name="doctor_id" label="Doctor" type="select" required :options="$doctors->mapWithKeys(fn ($d) => [$d->id => $d->name.($d->specialization ? ' — '.$d->specialization : '')])" :value="$appointment->doctor_id" />
        <x-field name="appointment_type" label="Appointment type" type="select" required :options="collect(settings()->appointmentTypes())->mapWithKeys(fn ($t) => [$t => $t])" :value="$appointment->appointment_type" />
        <x-field name="appointment_date" label="Date" type="date" required :value="$appointment->appointment_date" :min="$editing ? null : today()->toDateString()" />
        <div>
            <label class="label" for="f_appointment_time">Time <span class="text-red-500">*</span></label>
            <input id="f_appointment_time" type="time" name="appointment_time" list="slot-list" required value="{{ old('appointment_time', $appointment->appointment_time ? substr((string) $appointment->appointment_time, 0, 5) : '') }}" class="input @error('appointment_time') input-error @enderror">
            <datalist id="slot-list">@foreach ($slots as $s)<option value="{{ $s }}">@endforeach</datalist>
            <p class="help">Clinic hours {{ settings('appointment_day_start') }}–{{ settings('appointment_day_end') }}, {{ settings('appointment_slot_minutes') }}-minute slots. Double-booking a doctor's slot is blocked.</p>
            @error('appointment_time')<p class="error">{{ $message }}</p>@enderror
        </div>
        <x-field class="md:col-span-2" name="reason" label="Reason for visit" :value="$appointment->reason" />
        <x-field class="md:col-span-2" name="notes" label="Notes" type="textarea" :value="$appointment->notes" />
    </div>
</div>
