@php
    $u = auth()->user();
    $tabs = ['overview' => 'Overview'];
    if ($clinical) {
        $tabs += ['history' => 'Medical History'];
    }
    $tabs += ['visits' => 'Visits'];
    if ($clinical) {
        $tabs += ['consultations' => 'Consultations', 'prescriptions' => 'Prescriptions', 'medications' => 'Medications', 'laboratory' => 'Laboratory Reports'];
    }
    $tabs += ['timeline' => 'Timeline'];
    $activeAllergies = $clinical ? $patient->allergies->where('status', \App\Enums\RecordStatus::Active) : collect();
@endphp
<x-layouts.app :title="$patient->full_name">
    <x-page-header :title="$patient->full_name" :back="route('patients.index')">
        <x-slot:meta>
            <div class="mt-1.5 flex flex-wrap items-center gap-x-3 gap-y-1 text-[13px] text-slate-500">
                <span class="font-mono text-xs font-medium text-slate-700">{{ $patient->patient_code }}</span>
                <span>{{ $patient->gender?->label() }}</span>
                <span>{{ $patient->currentAge() !== null ? $patient->currentAge().' years' : 'Age unknown' }}</span>
                @if ($patient->blood_group)<x-badge color="red">{{ $patient->blood_group }}</x-badge>@endif
                @if ($patient->phone)<span>{{ $patient->phone }}</span>@endif
                @if ($patient->trashed())<x-badge color="slate">Archived</x-badge>@endif
            </div>
        </x-slot:meta>
        <x-slot:actions>
            @unless ($patient->trashed())
                @can('visits.manage')<a href="{{ route('visits.create', ['patient' => $patient->id]) }}" class="btn btn-primary"><x-icon name="plus" /> New visit</a>@endcan
                @can('appointments.manage')<a href="{{ route('appointments.create', ['patient' => $patient->id]) }}" class="btn btn-secondary"><x-icon name="calendar" /> Book</a>@endcan
                @can('repeat', [App\Models\Prescription::class, $patient])<a href="{{ route('prescriptions.repeat', $patient) }}" class="btn btn-secondary"><x-icon name="duplicate" /> Repeat Rx</a>@endcan
                @can('create', App\Models\LaboratoryRequest::class)<a href="{{ route('laboratory.requests.create', ['patient' => $patient->id]) }}" class="btn btn-secondary"><x-icon name="beaker" /> Lab request</a>@endcan
            @endunless
            <div class="relative" x-data="{ open: false }" x-on:click.outside="open = false">
                <button type="button" class="btn btn-secondary" x-on:click="open = !open" :aria-expanded="open">More <x-icon name="chevron-down" class="size-3.5" /></button>
                <div x-show="open" x-cloak class="absolute right-0 z-20 mt-1 w-56 overflow-hidden rounded-xl border border-slate-200 bg-white py-1 shadow-lg">
                    @can('update', $patient)<a href="{{ route('patients.edit', $patient) }}" class="flex items-center gap-2 px-4 py-2 text-[13px] hover:bg-slate-50"><x-icon name="pencil" /> Edit details</a>@endcan
                    <a href="{{ route('patients.print', $patient) }}" target="_blank" class="flex items-center gap-2 px-4 py-2 text-[13px] hover:bg-slate-50"><x-icon name="printer" /> Print profile</a>
                    @if ($clinical)<a href="{{ route('patients.history-pdf', $patient) }}" target="_blank" class="flex items-center gap-2 px-4 py-2 text-[13px] hover:bg-slate-50"><x-icon name="download" /> Medical history PDF</a>@endif
                    @can('delete', $patient)
                        @unless ($patient->trashed())
                            <form method="POST" action="{{ route('patients.destroy', $patient) }}" data-confirm="The patient will be hidden from lists. All clinical records are kept and the patient can be restored." data-confirm-title="Archive {{ $patient->full_name }}?" data-confirm-label="Archive" data-confirm-danger>
                                @csrf @method('DELETE')<button class="flex w-full items-center gap-2 px-4 py-2 text-left text-[13px] text-red-600 hover:bg-red-50"><x-icon name="archive" /> Archive patient</button>
                            </form>
                        @endunless
                    @endcan
                </div>
            </div>
        </x-slot:actions>
    </x-page-header>

    @if ($activeAllergies->isNotEmpty())
        <div class="mb-4 flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3" role="alert">
            <x-icon name="warning" class="mt-0.5 size-5 shrink-0 text-red-600" />
            <div class="text-[13px] text-red-900"><p class="font-semibold">Allergies</p>
                <p>@foreach ($activeAllergies as $a)<span class="font-medium">{{ $a->allergen }}</span> <span class="text-red-700">({{ $a->allergy_type->label() }}, {{ strtolower($a->severity->label()) }}{{ $a->reaction ? ' – '.$a->reaction : '' }})</span>@if(! $loop->last), @endif @endforeach</p>
            </div>
        </div>
    @endif

    <nav class="mb-6 overflow-x-auto border-b border-slate-200 no-print" aria-label="Patient record sections">
        <div class="flex gap-6">
            @foreach ($tabs as $key => $label)
                <a href="{{ route('patients.show', [$patient, 'tab' => $key]) }}" @class(['tab', 'tab-active' => $tab === $key, 'tab-inactive' => $tab !== $key]) @if($tab === $key) aria-current="page" @endif>{{ $label }}</a>
            @endforeach
        </div>
    </nav>

    @include('patients.tabs.'.(array_key_exists($tab, $tabs) ? $tab : 'overview'))
</x-layouts.app>
