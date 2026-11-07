<x-layouts.print title="Patient Profile" :subtitle="$patient->patient_code">
    <section class="avoid-break mb-5">
        <h2 class="mb-2 text-[11px] font-bold uppercase tracking-wide text-slate-500">Patient information</h2>
        <table class="w-full text-[12px]">
            <tr><td class="w-36 py-0.5 text-slate-500">Name</td><td class="font-semibold">{{ $patient->full_name }}</td><td class="w-36 text-slate-500">Patient ID</td><td class="font-mono">{{ $patient->patient_code }}</td></tr>
            <tr><td class="py-0.5 text-slate-500">Gender / Age</td><td>{{ $patient->gender?->label() }} · {{ $patient->currentAge() ?? '—' }}</td><td class="text-slate-500">Date of birth</td><td>{{ fmt_date($patient->date_of_birth) }}</td></tr>
            <tr><td class="py-0.5 text-slate-500">Phone</td><td>{{ $patient->phone ?? '—' }}</td><td class="text-slate-500">Blood group</td><td>{{ $patient->blood_group ?? '—' }}</td></tr>
            <tr><td class="py-0.5 text-slate-500">Address</td><td colspan="3">{{ $patient->address ?? '—' }}</td></tr>
            <tr><td class="py-0.5 text-slate-500">Emergency contact</td><td colspan="3">{{ $patient->emergency_contact_name ? $patient->emergency_contact_name.' ('.($patient->emergency_contact_relation ?? '—').') '.$patient->emergency_contact_phone : '—' }}</td></tr>
            <tr><td class="py-0.5 text-slate-500">Registered</td><td colspan="3">{{ fmt_date($patient->registration_date) }}</td></tr>
        </table>
    </section>

    @if ($clinical)
        <section class="avoid-break mb-5">
            <h2 class="mb-2 text-[11px] font-bold uppercase tracking-wide text-slate-500">Medical overview</h2>
            <table class="w-full text-[12px]">
                <tr><td class="w-36 py-0.5 align-top text-slate-500">Allergies</td><td>{{ $patient->allergies->where('status', \App\Enums\RecordStatus::Active)->map(fn ($a) => $a->allergen.' ('.strtolower($a->severity->label()).')')->implode(', ') ?: 'None recorded' }}</td></tr>
                <tr><td class="py-0.5 align-top text-slate-500">Current conditions</td><td>{{ $patient->conditions->where('category', \App\Enums\ConditionCategory::Condition)->where('status', \App\Enums\ConditionStatus::Current)->pluck('name')->implode(', ') ?: 'None recorded' }}</td></tr>
                <tr><td class="py-0.5 align-top text-slate-500">Past history</td><td>{{ $patient->conditions->where('category', '!=', \App\Enums\ConditionCategory::Condition)->map(fn ($c) => $c->name.' ('.$c->category->label().')')->implode(', ') ?: '—' }}</td></tr>
                <tr><td class="py-0.5 align-top text-slate-500">Current medication</td><td>{{ $patient->medicationCourses->pluck('medicine_name')->concat($patient->otherMedications->where('status', \App\Enums\PatientMedicationStatus::Current)->map(fn ($m) => $m->medicine_name.' (other source)'))->implode(', ') ?: 'None recorded' }}</td></tr>
            </table>
        </section>
    @endif

    <section>
        <h2 class="mb-2 text-[11px] font-bold uppercase tracking-wide text-slate-500">Recent visits</h2>
        <table class="w-full border-collapse text-[12px]">
            <thead><tr class="border-b border-slate-300 text-left"><th class="py-1">Date</th><th>Visit</th><th>Type</th><th>Doctor</th><th>Complaint</th><th>Status</th></tr></thead>
            <tbody>
            @forelse ($visits as $v)
                <tr class="border-b border-slate-100"><td class="py-1">{{ fmt_datetime($v->visit_at) }}</td><td class="font-mono">{{ $v->visit_number }}</td><td>{{ $v->visitTypeLabel() }}</td><td>{{ $v->doctor->name }}</td><td>{{ $v->chief_complaint ?? '—' }}</td><td>{{ $v->status->label() }}</td></tr>
            @empty
                <tr><td colspan="6" class="py-2 text-slate-500">No visits recorded.</td></tr>
            @endforelse
            </tbody>
        </table>
    </section>
</x-layouts.print>
