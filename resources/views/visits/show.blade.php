@php $u = auth()->user(); $open = in_array($visit->status, [App\Enums\VisitStatus::Waiting, App\Enums\VisitStatus::InConsultation], true); @endphp
<x-layouts.app :title="$visit->visit_number">
    <x-page-header :title="'Visit '.$visit->visit_number" :subtitle="fmt_datetime($visit->visit_at).' · '.$visit->visitTypeLabel()" :back="route('visits.index')">
        <x-slot:meta><div class="mt-2"><x-badge :status="$visit->status" /></div></x-slot:meta>
        <x-slot:actions>
            @if ($open)
                @can('consult', $visit)
                    <form method="POST" action="{{ route('visits.consult', $visit) }}">@csrf<button class="btn btn-primary"><x-icon name="stethoscope" /> {{ $visit->status === App\Enums\VisitStatus::InConsultation ? 'Open consultation' : 'Start consultation' }}</button></form>
                @endcan
                @can('cancel', $visit)
                    <form method="POST" action="{{ route('visits.cancel', $visit) }}" data-confirm="The visit will be marked as cancelled. It stays in the patient's history." data-confirm-title="Cancel this visit?" data-confirm-reason="reason" data-confirm-label="Cancel visit" data-confirm-danger>@csrf<button class="btn btn-secondary">Cancel visit</button></form>
                @endcan
            @endif
        </x-slot:actions>
    </x-page-header>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
        <div class="space-y-6 xl:col-span-2">
            <section class="card">
                <div class="card-header"><h2 class="card-title">Encounter</h2></div>
                <div class="card-body">
                    <dl class="dl">
                        <dt>Patient</dt><dd><a href="{{ route('patients.show', $visit->patient) }}" class="font-medium text-brand-700 hover:underline">{{ $visit->patient->full_name }}</a> <span class="text-slate-500">{{ $visit->patient->patient_code }} · {{ $visit->patient->gender?->label() }}, {{ $visit->patient->currentAge() ?? '—' }}</span></dd>
                        <dt>Doctor</dt><dd>{{ $visit->doctor->displayName() }}</dd>
                        <dt>Arrived</dt><dd>{{ fmt_datetime($visit->visit_at) }}</dd>
                        <dt>Appointment</dt><dd>@if($visit->appointment)<a href="{{ route('appointments.show', $visit->appointment) }}" class="text-brand-700 hover:underline">{{ $visit->appointment->appointment_number }}</a> · {{ $visit->appointment->appointment_type }}@else Walk-in / unscheduled @endif</dd>
                        <dt>Chief complaint</dt><dd>{{ $visit->chief_complaint ?? '—' }}</dd>
                        @if ($visit->notes)<dt>Front-desk notes</dt><dd class="whitespace-pre-line">{{ $visit->notes }}</dd>@endif
                        @if ($visit->completed_at)<dt>Completed</dt><dd>{{ fmt_datetime($visit->completed_at) }}</dd>@endif
                        <dt>Recorded by</dt><dd>{{ $visit->creator?->name ?? '—' }}</dd>
                    </dl>
                </div>
            </section>

            @if ($clinical)
                @forelse ($visit->consultations as $c)
                    <section class="card">
                        <div class="card-header">
                            <div><h2 class="card-title">Consultation {{ $c->consultation_number }}</h2><p class="text-xs text-slate-500">{{ $c->doctor->name }} · {{ fmt_datetime($c->completed_at ?? $c->created_at) }}</p></div>
                            <div class="flex items-center gap-2"><x-badge :status="$c->status" />
                                @can('update', $c)<a href="{{ route('consultations.edit', $c) }}" class="btn btn-primary btn-sm">Continue</a>@else<a href="{{ route('consultations.show', $c) }}" class="btn btn-secondary btn-sm">Open</a>@endcan
                            </div>
                        </div>
                        <div class="card-body">
                            <dl class="dl">
                                <dt>Symptoms</dt><dd class="prose-clinical">{{ $c->symptoms ?: '—' }}</dd>
                                @if ($c->clinical_observations)<dt>Observations</dt><dd class="prose-clinical">{{ $c->clinical_observations }}</dd>@endif
                                @if ($c->vitals && ! $c->vitals->isEmpty())<dt>Vitals</dt><dd>@include('consultations._vitals-inline', ['v' => $c->vitals])</dd>@endif
                                <dt>Diagnosis</dt><dd class="prose-clinical">{{ $c->diagnosis ?: '—' }}</dd>
                                @if ($c->treatment_plan)<dt>Treatment plan</dt><dd class="prose-clinical">{{ $c->treatment_plan }}</dd>@endif
                                @if ($c->prescription)
                                    <dt>Prescription</dt><dd><a href="{{ route('prescriptions.show', $c->prescription) }}" class="text-brand-700 hover:underline">{{ $c->prescription->prescription_number }}</a> · {{ $c->prescription->items->pluck('medicine_name')->implode(', ') }}</dd>
                                @endif
                                @if ($c->laboratoryRequests->isNotEmpty())
                                    <dt>Lab requests</dt><dd>@foreach ($c->laboratoryRequests as $r)<a href="{{ route('laboratory.requests.show', $r) }}" class="text-brand-700 hover:underline">{{ $r->test_name }}</a> <x-badge :status="$r->status" />@if(! $loop->last), @endif @endforeach</dd>
                                @endif
                                @if ($c->follow_up_date)<dt>Follow-up</dt><dd>{{ fmt_date($c->follow_up_date) }}{{ $c->follow_up_instructions ? ' — '.$c->follow_up_instructions : '' }}</dd>@endif
                            </dl>
                        </div>
                    </section>
                @empty
                    <section class="card"><x-empty icon="stethoscope" title="No consultation yet">@can('consult', $visit) Start the consultation when the patient is with you. @else The doctor will record the consultation. @endcan</x-empty></section>
                @endforelse
            @endif
        </div>

        @if ($clinical)
            <section class="card self-start">
                <div class="card-header"><h2 class="card-title">Reports for this visit</h2></div>
                @forelse ($visit->laboratoryReports as $r)
                    <a href="{{ route('laboratory.reports.show', $r) }}" class="flex items-center justify-between gap-2 border-b border-slate-100 px-5 py-3 last:border-0 hover:bg-slate-50"><div><p class="text-[13px] font-medium">{{ $r->test_name }}</p><p class="text-xs text-slate-500">{{ fmt_date($r->report_date) }}</p></div><x-badge :status="$r->status" /></a>
                @empty
                    <x-empty icon="beaker" title="No reports linked" compact />
                @endforelse
            </section>
        @endif
    </div>
</x-layouts.app>
