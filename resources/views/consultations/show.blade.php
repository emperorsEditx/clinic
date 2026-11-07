@php $rxList = $c->prescriptions->where('status', '!=', App\Enums\PrescriptionStatus::Draft); @endphp
<x-layouts.app :title="$c->consultation_number">
    <x-page-header :title="'Consultation '.$c->consultation_number" :subtitle="$c->patient->full_name.' · '.fmt_datetime($c->completed_at ?? $c->created_at).' · '.$c->doctor->displayName()" :back="route('patients.show', [$c->patient, 'tab' => 'consultations'])">
        <x-slot:meta><div class="mt-2 flex gap-2"><x-badge :status="$c->status" />@if($c->amendment_count)<x-badge color="amber">Amended {{ $c->amendment_count }}×</x-badge>@endif</div></x-slot:meta>
        <x-slot:actions>
            @can('update', $c)<a href="{{ route('consultations.edit', $c) }}" class="btn btn-primary">Continue draft</a>@endcan
            @can('amend', $c)<a href="{{ route('consultations.amend', $c) }}" class="btn btn-secondary"><x-icon name="pencil" /> Amend</a>@endcan
            <a href="{{ route('visits.show', $c->visit) }}" class="btn btn-secondary">Visit {{ $c->visit->visit_number }}</a>
        </x-slot:actions>
    </x-page-header>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
        <div class="space-y-6 xl:col-span-2">
            <section class="card">
                <div class="card-header"><h2 class="card-title">Clinical record</h2></div>
                <div class="card-body">
                    <dl class="dl">
                        <dt>Patient</dt><dd><a href="{{ route('patients.show', $c->patient) }}" class="font-medium text-brand-700 hover:underline">{{ $c->patient->full_name }}</a> <span class="text-slate-500">{{ $c->patient->patient_code }}</span></dd>
                        <dt>Symptoms</dt><dd class="prose-clinical">{{ $c->symptoms ?: '—' }}</dd>
                        <dt>Vital signs</dt><dd>@if($c->vitals && ! $c->vitals->isEmpty())@include('consultations._vitals-inline', ['v' => $c->vitals])@else — @endif</dd>
                        <dt>Observations</dt><dd class="prose-clinical">{{ $c->clinical_observations ?: '—' }}</dd>
                        <dt>Diagnosis</dt><dd class="prose-clinical font-medium">{{ $c->diagnosis ?: '—' }}</dd>
                        <dt>Treatment plan</dt><dd class="prose-clinical">{{ $c->treatment_plan ?: '—' }}</dd>
                        <dt>Follow-up</dt><dd>{{ $c->follow_up_date ? fmt_date($c->follow_up_date) : '—' }}@if($c->follow_up_instructions)<div class="prose-clinical">{{ $c->follow_up_instructions }}</div>@endif @if($c->followUp)<x-badge :status="$c->followUp->status" />@endif</dd>
                        <dt>Doctor's notes</dt><dd class="prose-clinical">{{ $c->doctor_notes ?: '—' }}</dd>
                    </dl>
                </div>
            </section>

            @foreach ($rxList as $rx)
                <section class="card">
                    <div class="card-header"><h2 class="card-title">Prescription {{ $rx->prescription_number }} @if($rx->version > 1)<span class="text-slate-500">v{{ $rx->version }}</span>@endif</h2>
                        <div class="flex items-center gap-2"><x-badge :status="$rx->status" /><a href="{{ route('prescriptions.show', $rx) }}" class="btn btn-ghost btn-sm">Open</a><a href="{{ route('prescriptions.pdf', $rx) }}" target="_blank" class="btn btn-ghost btn-sm"><x-icon name="printer" /></a></div>
                    </div>
                    @include('prescriptions._items-table', ['items' => $rx->items])
                </section>
            @endforeach

            @if ($c->amendments->isNotEmpty())
                <section class="card">
                    <div class="card-header"><h2 class="card-title">Amendment history</h2><span class="text-xs text-slate-500">Original values are preserved</span></div>
                    @foreach ($c->amendments->sortByDesc('id') as $am)
                        <div class="border-b border-slate-100 px-5 py-4 last:border-0">
                            <p class="text-[13px]"><span class="font-medium">{{ $am->author->name }}</span> <span class="text-xs text-slate-500">{{ fmt_datetime($am->created_at) }}</span></p>
                            <p class="mt-0.5 text-xs text-slate-600">Reason: {{ $am->reason }}</p>
                            <x-changes :changes="$am->changes" class="mt-2" />
                        </div>
                    @endforeach
                </section>
            @endif
        </div>

        <section class="card self-start">
            <div class="card-header"><h2 class="card-title">Laboratory</h2></div>
            @forelse ($c->laboratoryRequests as $r)
                <div class="border-b border-slate-100 px-5 py-3 last:border-0">
                    <div class="flex items-center justify-between gap-2"><a href="{{ route('laboratory.requests.show', $r) }}" class="text-[13px] font-medium hover:text-brand-700">{{ $r->test_name }}</a><x-badge :status="$r->status" /></div>
                    @foreach ($r->reports as $rep)<a href="{{ route('laboratory.reports.show', $rep) }}" class="mt-1 block text-xs text-brand-700 hover:underline">Report {{ $rep->report_number }} · {{ fmt_date($rep->report_date) }}</a>@endforeach
                </div>
            @empty
                <x-empty icon="beaker" title="No tests requested" compact />
            @endforelse
        </section>
    </div>
</x-layouts.app>
