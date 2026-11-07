@php
    $canManage = auth()->user()->can('manageHistory', $patient);
    $groups = [
        ['Current medical conditions', \App\Enums\ConditionCategory::Condition, \App\Enums\ConditionStatus::Current],
        ['Historical conditions', \App\Enums\ConditionCategory::Condition, \App\Enums\ConditionStatus::Historical],
        ['Previous diseases', \App\Enums\ConditionCategory::PreviousDisease, null],
        ['Previous surgeries', \App\Enums\ConditionCategory::Surgery, null],
        ['Family medical history', \App\Enums\ConditionCategory::FamilyHistory, null],
    ];
@endphp
<datalist id="freq-list">@foreach (config('clinic.frequencies') as $f)<option value="{{ $f }}">@endforeach</datalist>

<div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
    <div class="space-y-6 xl:col-span-2">
        <section class="card">
            <div class="card-header"><h2 class="card-title">Conditions, diseases &amp; surgeries</h2>
                @if ($canManage)<button type="button" class="btn btn-secondary btn-sm" x-data x-on:click="$dispatch('open-modal', 'add-condition')"><x-icon name="plus" /> Add entry</button>@endif
            </div>
            <div class="divide-y divide-slate-100">
                @foreach ($groups as [$heading, $cat, $status])
                    @php $rows = $patient->conditions->where('category', $cat)->when($status, fn ($c) => $c->where('status', $status)); @endphp
                    <div class="px-5 py-4">
                        <h3 class="mb-2 text-[11px] font-semibold uppercase tracking-wide text-slate-500">{{ $heading }}</h3>
                        @forelse ($rows as $c)
                            <div class="flex items-start justify-between gap-3 py-1.5">
                                <div class="min-w-0">
                                    <p class="text-[13px] font-medium text-slate-900">{{ $c->name }}@if($c->relation) <span class="font-normal text-slate-500">— {{ $c->relation }}</span>@endif
                                        @if ($cat !== \App\Enums\ConditionCategory::Condition && $c->status === \App\Enums\ConditionStatus::Current) <x-badge color="amber">Current</x-badge>@endif</p>
                                    <p class="text-xs text-slate-500">
                                        @if($c->onset_date){{ fmt_date($c->onset_date) }}@endif @if($c->resolved_date) → {{ fmt_date($c->resolved_date) }}@endif
                                        @if($c->onset_date || $c->resolved_date) · @endif Recorded by {{ $c->recorder?->name ?? 'system' }} on {{ fmt_date($c->created_at) }}
                                    </p>
                                    @if ($c->notes)<p class="mt-0.5 text-xs text-slate-600">{{ $c->notes }}</p>@endif
                                </div>
                                @include('patients._history-actions', ['type' => 'condition', 'entry' => $c, 'label' => $c->name])
                            </div>
                        @empty
                            <p class="text-[13px] text-slate-400">None recorded</p>
                        @endforelse
                    </div>
                @endforeach
            </div>
        </section>

        <section class="card">
            <div class="card-header"><h2 class="card-title">Clinical notes</h2>
                @if ($canManage)<button type="button" class="btn btn-secondary btn-sm" x-data x-on:click="$dispatch('open-modal', 'add-note')"><x-icon name="plus" /> Add note</button>@endif
            </div>
            @forelse ($patient->medicalHistories as $n)
                <div class="flex items-start justify-between gap-3 border-b border-slate-100 px-5 py-3 last:border-0">
                    <div class="min-w-0">
                        @if ($n->title)<p class="text-[13px] font-medium">{{ $n->title }}</p>@endif
                        <p class="prose-clinical">{{ $n->notes }}</p>
                        <p class="mt-1 text-xs text-slate-500">{{ $n->recorder?->name ?? 'system' }} · {{ fmt_datetime($n->created_at) }}@if($n->updated_at && $n->updated_at->ne($n->created_at)) · edited {{ fmt_datetime($n->updated_at) }}@endif</p>
                    </div>
                    @include('patients._history-actions', ['type' => 'note', 'entry' => $n, 'label' => $n->title ?: 'note'])
                </div>
            @empty
                <x-empty icon="document" title="No notes" compact />
            @endforelse
        </section>
    </div>

    <div class="space-y-6">
        <section class="card">
            <div class="card-header"><h2 class="card-title">Allergies</h2>
                @if ($canManage)<button type="button" class="btn btn-secondary btn-sm" x-data x-on:click="$dispatch('open-modal', 'add-allergy')"><x-icon name="plus" /> Add</button>@endif
            </div>
            @forelse ($patient->allergies as $a)
                <div @class(['flex items-start justify-between gap-3 border-b border-slate-100 px-5 py-3 last:border-0', 'opacity-60' => $a->status === \App\Enums\RecordStatus::Inactive])>
                    <div class="min-w-0">
                        <p class="text-[13px] font-medium">{{ $a->allergen }} <x-badge :status="$a->severity" /></p>
                        <p class="text-xs text-slate-500">{{ $a->allergy_type->label() }}{{ $a->reaction ? ' · '.$a->reaction : '' }}{{ $a->status === \App\Enums\RecordStatus::Inactive ? ' · inactive' : '' }}</p>
                        <p class="text-[11px] text-slate-400">{{ $a->recorder?->name ?? 'system' }} · {{ fmt_date($a->created_at) }}</p>
                    </div>
                    @include('patients._history-actions', ['type' => 'allergy', 'entry' => $a, 'label' => $a->allergen])
                </div>
            @empty
                <x-empty icon="shield" title="No allergies recorded" compact />
            @endforelse
        </section>

        <section class="card">
            <div class="card-header"><h2 class="card-title">Existing &amp; previous medications</h2><a href="{{ route('patients.show', [$patient, 'tab' => 'medications']) }}" class="text-xs font-medium text-brand-700 hover:underline">Manage</a></div>
            <div class="card-body space-y-3 text-[13px]">
                <div><p class="mb-1 text-[11px] font-semibold uppercase tracking-wide text-slate-500">Current — clinic prescribed</p>
                    @forelse ($activeCourses as $m)<p>{{ $m->medicine_name }} <span class="text-xs text-slate-400">until {{ fmt_date($m->expected_end_date) }}</span></p>@empty<p class="text-slate-400">None</p>@endforelse</div>
                <div><p class="mb-1 text-[11px] font-semibold uppercase tracking-wide text-slate-500">Current — other sources</p>
                    @forelse ($patient->otherMedications->where('status', \App\Enums\PatientMedicationStatus::Current) as $m)<p>{{ $m->medicine_name }} <span class="text-xs text-slate-400">{{ $m->dosage }} {{ $m->frequency }}</span></p>@empty<p class="text-slate-400">None</p>@endforelse</div>
                <div><p class="mb-1 text-[11px] font-semibold uppercase tracking-wide text-slate-500">Previous</p>
                    @php $prev = $patient->medicationCourses->where('status', '!=', \App\Enums\MedicationCourseStatus::Active)->take(8); $prevOther = $patient->otherMedications->where('status', \App\Enums\PatientMedicationStatus::Previous); @endphp
                    @forelse ($prev->concat($prevOther) as $m)<p class="text-slate-600">{{ $m->medicine_name }}</p>@empty<p class="text-slate-400">None</p>@endforelse</div>
            </div>
        </section>
    </div>
</div>

@if ($canManage)
    @foreach (['condition' => 'Add condition / history entry', 'allergy' => 'Add allergy', 'note' => 'Add clinical note'] as $type => $title)
        <x-modal :name="'add-'.$type" :title="$title">
            <form method="POST" action="{{ route('patients.history.store', [$patient, $type]) }}">
                @csrf
                @include('patients._history-fields', ['type' => $type, 'entry' => null])
                <div class="mt-5 flex justify-end gap-2"><button type="button" class="btn btn-secondary" x-on:click="$dispatch('close-modal', 'add-{{ $type }}')">Cancel</button><button class="btn btn-primary">Save</button></div>
            </form>
        </x-modal>
    @endforeach
@endif
