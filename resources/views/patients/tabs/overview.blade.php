<div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
    <div class="space-y-6 xl:col-span-2">
        @if ($clinical)
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                @foreach (['visits' => ['Visits', 'visits'], 'consultations' => ['Consultations', 'consultations'], 'prescriptions' => ['Prescriptions', 'prescriptions'], 'lab' => ['Lab reports', 'laboratory']] as $k => [$label, $t])
                    <a href="{{ route('patients.show', [$patient, 'tab' => $t]) }}" class="card p-4 hover:border-brand-300"><p class="text-xs text-slate-500">{{ $label }}</p><p class="mt-0.5 text-xl font-semibold tabular-nums">{{ $counts[$k] }}</p></a>
                @endforeach
            </div>

            <section class="card">
                <div class="card-header"><h2 class="card-title">Medical overview</h2>@can('manageHistory', $patient)<a href="{{ route('patients.show', [$patient, 'tab' => 'history']) }}" class="text-xs font-medium text-brand-700 hover:underline">Manage history</a>@endcan</div>
                <div class="grid grid-cols-1 gap-6 p-5 md:grid-cols-3">
                    <div>
                        <h3 class="mb-2 text-[11px] font-semibold uppercase tracking-wide text-slate-500">Current conditions</h3>
                        @php $current = $patient->conditions->where('category', \App\Enums\ConditionCategory::Condition)->where('status', \App\Enums\ConditionStatus::Current); @endphp
                        @forelse ($current as $c)<p class="text-[13px]">{{ $c->name }}@if($c->onset_date) <span class="text-xs text-slate-400">since {{ $c->onset_date->format('Y') }}</span>@endif</p>@empty<p class="text-[13px] text-slate-400">None recorded</p>@endforelse
                    </div>
                    <div>
                        <h3 class="mb-2 text-[11px] font-semibold uppercase tracking-wide text-slate-500">Allergies</h3>
                        @forelse ($patient->allergies->where('status', \App\Enums\RecordStatus::Active) as $a)<p class="text-[13px]">{{ $a->allergen }} <x-badge :status="$a->severity" /></p>@empty<p class="text-[13px] text-slate-400">No known allergies recorded</p>@endforelse
                    </div>
                    <div>
                        <h3 class="mb-2 text-[11px] font-semibold uppercase tracking-wide text-slate-500">Current medications</h3>
                        @foreach ($activeCourses as $m)<p class="text-[13px]">{{ $m->medicine_name }} <span class="text-xs text-slate-400">until {{ fmt_date($m->expected_end_date) }}</span></p>@endforeach
                        @foreach ($patient->otherMedications->where('status', \App\Enums\PatientMedicationStatus::Current) as $m)<p class="text-[13px]">{{ $m->medicine_name }} <x-badge color="orange">Other source</x-badge></p>@endforeach
                        @if ($activeCourses->isEmpty() && $patient->otherMedications->where('status', \App\Enums\PatientMedicationStatus::Current)->isEmpty())<p class="text-[13px] text-slate-400">None recorded</p>@endif
                    </div>
                </div>
            </section>

            @if ($lastConsultation)
                <section class="card">
                    <div class="card-header"><h2 class="card-title">Last consultation</h2><a href="{{ route('consultations.show', $lastConsultation) }}" class="text-xs font-medium text-brand-700 hover:underline">{{ $lastConsultation->consultation_number }}</a></div>
                    <div class="card-body">
                        <dl class="dl">
                            <dt>Date</dt><dd>{{ fmt_datetime($lastConsultation->completed_at) }} · {{ $lastConsultation->doctor->name }}</dd>
                            <dt>Diagnosis</dt><dd class="prose-clinical">{{ $lastConsultation->diagnosis }}</dd>
                            @if ($lastConsultation->treatment_plan)<dt>Treatment plan</dt><dd class="prose-clinical">{{ $lastConsultation->treatment_plan }}</dd>@endif
                            @if ($lastConsultation->vitals && ! $lastConsultation->vitals->isEmpty())<dt>Vitals</dt><dd>@include('consultations._vitals-inline', ['v' => $lastConsultation->vitals])</dd>@endif
                        </dl>
                    </div>
                </section>
            @endif
        @endif

        <section class="card">
            <div class="card-header"><h2 class="card-title">Recent visits</h2><a href="{{ route('patients.show', [$patient, 'tab' => 'visits']) }}" class="text-xs font-medium text-brand-700 hover:underline">All visits ({{ $counts['visits'] }})</a></div>
            @if ($recentVisits->isEmpty())
                <x-empty icon="clipboard" title="No visits yet" compact />
            @else
                <ul class="divide-y divide-slate-100">
                    @foreach ($recentVisits as $v)
                        <li class="flex items-center justify-between gap-3 px-5 py-3"><div class="min-w-0"><a href="{{ route('visits.show', $v) }}" class="font-medium hover:text-brand-700">{{ fmt_datetime($v->visit_at) }}</a><p class="truncate text-xs text-slate-500">{{ $v->visit_number }} · {{ $v->visitTypeLabel() }} · {{ $v->doctor->name }}{{ $v->chief_complaint ? ' · '.$v->chief_complaint : '' }}</p></div><x-badge :status="$v->status" /></li>
                    @endforeach
                </ul>
            @endif
        </section>
    </div>

    <div class="space-y-6">
        <section class="card">
            <div class="card-header"><h2 class="card-title">Patient information</h2>@can('update', $patient)<a href="{{ route('patients.edit', $patient) }}" class="text-xs font-medium text-brand-700 hover:underline">Edit</a>@endcan</div>
            <div class="card-body">
                <dl class="dl sm:grid-cols-[8rem_1fr]">
                    <dt>Patient ID</dt><dd class="font-mono text-xs">{{ $patient->patient_code }}</dd>
                    <dt>Date of birth</dt><dd>{{ fmt_date($patient->date_of_birth) }}</dd>
                    <dt>Age</dt><dd>{{ $patient->currentAge() ?? '—' }}</dd>
                    <dt>Gender</dt><dd>{{ $patient->gender?->label() }}</dd>
                    <dt>Blood group</dt><dd>{{ $patient->blood_group ?? '—' }}</dd>
                    <dt>Phone</dt><dd>{{ $patient->phone ?? '—' }}</dd>
                    <dt>Email</dt><dd>{{ $patient->email ?? '—' }}</dd>
                    <dt>Address</dt><dd>{{ $patient->address ?? '—' }}</dd>
                    <dt>Registered</dt><dd>{{ fmt_date($patient->registration_date) }}</dd>
                    <dt>Emergency</dt><dd>@if($patient->emergency_contact_name){{ $patient->emergency_contact_name }}{{ $patient->emergency_contact_relation ? ' ('.$patient->emergency_contact_relation.')' : '' }}<br><span class="text-slate-500">{{ $patient->emergency_contact_phone }}</span>@else — @endif</dd>
                </dl>
                @if ($patient->notes)<div class="mt-4 rounded-lg bg-slate-50 p-3 text-[13px] text-slate-700"><p class="mb-1 text-[11px] font-semibold uppercase tracking-wide text-slate-500">Notes</p><p class="whitespace-pre-line">{{ $patient->notes }}</p></div>@endif
            </div>
        </section>

        @can('appointments.view')
            <section class="card">
                <div class="card-header"><h2 class="card-title">Upcoming appointments</h2></div>
                @forelse ($upcomingAppointments as $a)
                    <a href="{{ route('appointments.show', $a) }}" class="flex items-center justify-between gap-3 border-b border-slate-100 px-5 py-3 last:border-0 hover:bg-slate-50"><div><p class="text-[13px] font-medium">{{ fmt_date($a->appointment_date) }} · {{ fmt_time($a->scheduledAt()) }}</p><p class="text-xs text-slate-500">{{ $a->appointment_type }} · {{ $a->doctor->name }}</p></div><x-badge :status="$a->status" /></a>
                @empty
                    <x-empty icon="calendar" title="No upcoming appointments" compact />
                @endforelse
            </section>
        @endcan
    </div>
</div>
