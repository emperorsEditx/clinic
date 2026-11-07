<x-layouts.app :title="$rx->prescription_number">
    <x-page-header :title="'Prescription '.$rx->prescription_number" :subtitle="$rx->patient->full_name.' · '.fmt_date($rx->prescription_date).' · '.$rx->doctor->displayName()" :back="route('patients.show', [$rx->patient, 'tab' => 'prescriptions'])">
        <x-slot:meta>
            <div class="mt-2 flex flex-wrap gap-2"><x-badge :status="$rx->status" />@if($rx->version > 1)<x-badge color="amber">Version {{ $rx->version }}</x-badge>@endif @if($rx->repeat_of_id)<x-badge color="indigo">Repeat prescription</x-badge>@endif</div>
        </x-slot:meta>
        <x-slot:actions>
            @can('update', $rx)<a href="{{ route('prescriptions.edit', $rx) }}" class="btn btn-primary"><x-icon name="pencil" /> Continue draft</a>@endcan
            @unless ($rx->isDraft())
                <a href="{{ route('prescriptions.pdf', $rx) }}" target="_blank" class="btn btn-secondary"><x-icon name="printer" /> Print</a>
                <a href="{{ route('prescriptions.pdf', [$rx, 'download' => 1]) }}" class="btn btn-secondary"><x-icon name="download" /> PDF</a>
            @endunless
            @can('amend', $rx)
                @unless ($rx->nextVersion)
                    <form method="POST" action="{{ route('prescriptions.amend', $rx) }}" data-confirm="A new draft version is created from this prescription. This version stays valid until you finalise the amendment." data-confirm-title="Amend prescription?" data-confirm-reason="reason" data-confirm-label="Create amendment">@csrf<button class="btn btn-secondary">Amend</button></form>
                @endunless
            @endcan
            @can('prescriptions.manage')
                @unless ($rx->isDraft())<button type="button" class="btn btn-ghost" x-data x-on:click="$dispatch('open-modal', 'save-template')">Save as template</button>@endunless
            @endcan
            @can('repeat', [App\Models\Prescription::class, $rx->patient])
                @if (in_array($rx->status, [App\Enums\PrescriptionStatus::Finalized, App\Enums\PrescriptionStatus::Superseded], true))<a href="{{ route('prescriptions.repeat', [$rx->patient, 'from' => $rx->id]) }}" class="btn btn-ghost"><x-icon name="duplicate" /> Repeat</a>@endif
            @endcan
        </x-slot:actions>
    </x-page-header>

    @if ($rx->status === App\Enums\PrescriptionStatus::Superseded && $rx->nextVersion)
        <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-[13px] text-amber-900">This version was superseded by <a href="{{ route('prescriptions.show', $rx->nextVersion) }}" class="font-medium underline">{{ $rx->nextVersion->prescription_number }} (v{{ $rx->nextVersion->version }})</a>. It is kept unchanged for the record.</div>
    @elseif ($rx->nextVersion && $rx->nextVersion->isDraft())
        <div class="mb-4 rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-[13px] text-blue-900">An amendment draft exists: <a href="{{ route('prescriptions.show', $rx->nextVersion) }}" class="font-medium underline">{{ $rx->nextVersion->prescription_number }}</a>. This version remains valid until it is finalised.</div>
    @endif

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
        <div class="space-y-6 xl:col-span-2">
            <section class="card">
                <div class="card-header"><h2 class="card-title">Medicines</h2></div>
                @include('prescriptions._items-table', ['items' => $rx->items])
                @if ($rx->general_instructions || $rx->notes)
                    <div class="border-t border-slate-100 p-5"><dl class="dl">
                        @if ($rx->general_instructions)<dt>General instructions</dt><dd class="prose-clinical">{{ $rx->general_instructions }}</dd>@endif
                        @if ($rx->notes)<dt>Notes</dt><dd class="prose-clinical">{{ $rx->notes }}</dd>@endif
                    </dl></div>
                @endif
            </section>

            @if ($rx->courses->isNotEmpty())
                <section class="card">
                    <div class="card-header"><h2 class="card-title">Medication continuation</h2></div>
                    <div class="overflow-x-auto"><table class="table">
                        <thead><tr><th>Medicine</th><th>Start</th><th>Days</th><th>Expected end</th><th>Status</th><th></th></tr></thead>
                        <tbody>
                        @foreach ($rx->courses as $course)
                            <tr><td class="font-medium">{{ $course->medicine_name }}</td><td>{{ fmt_date($course->start_date) }}</td><td>{{ $course->duration_days }}</td><td>{{ fmt_date($course->expected_end_date) }}</td><td><x-badge :status="$course->status" /></td><td class="text-right">@include('patients._course-status', ['course' => $course])</td></tr>
                        @endforeach
                        </tbody>
                    </table></div>
                </section>
            @endif
        </div>

        <section class="card self-start">
            <div class="card-header"><h2 class="card-title">Record</h2></div>
            <div class="card-body">
                <dl class="dl sm:grid-cols-[8rem_1fr]">
                    <dt>Patient</dt><dd><a href="{{ route('patients.show', $rx->patient) }}" class="text-brand-700 hover:underline">{{ $rx->patient->full_name }}</a><br><span class="text-xs text-slate-500">{{ $rx->patient->patient_code }}</span></dd>
                    <dt>Consultation</dt><dd><a href="{{ route('consultations.show', $rx->consultation) }}" class="text-brand-700 hover:underline">{{ $rx->consultation->consultation_number }}</a></dd>
                    <dt>Prescribed by</dt><dd>{{ $rx->doctor->displayName() }}</dd>
                    <dt>Finalised</dt><dd>{{ fmt_datetime($rx->finalized_at) }}</dd>
                    @if ($rx->previousVersion)<dt>Replaces</dt><dd><a href="{{ route('prescriptions.show', $rx->previousVersion) }}" class="text-brand-700 hover:underline">{{ $rx->previousVersion->prescription_number }} (v{{ $rx->previousVersion->version }})</a></dd><dt>Amendment reason</dt><dd>{{ $rx->amendment_reason }}</dd>@endif
                    @if ($rx->repeatOf)<dt>Repeat of</dt><dd><a href="{{ route('prescriptions.show', $rx->repeatOf) }}" class="text-brand-700 hover:underline">{{ $rx->repeatOf->prescription_number }}</a></dd><dt>Reason</dt><dd>{{ $rx->repeat_reason }}</dd><dt>Authorised by</dt><dd>{{ $rx->authorizer?->name }} · {{ fmt_datetime($rx->authorized_at) }}</dd>@endif
                    @if ($rx->repeats->isNotEmpty())<dt>Repeated as</dt><dd>@foreach ($rx->repeats as $r)<a href="{{ route('prescriptions.show', $r) }}" class="block text-brand-700 hover:underline">{{ $r->prescription_number }}</a>@endforeach</dd>@endif
                </dl>
            </div>
        </section>
    </div>

    @can('prescriptions.manage')
        <x-modal name="save-template" title="Save as prescription template">
            <form method="POST" action="{{ route('prescriptions.template', $rx) }}" class="space-y-4">
                @csrf
                <x-field name="name" label="Template name" required placeholder="e.g. Seasonal allergy" />
                <label class="flex items-center gap-2 text-[13px]"><input type="checkbox" name="is_shared" value="1" class="rounded border-slate-300"> Share with other doctors</label>
                <p class="text-xs text-slate-500">Templates are a starting point only — review every medicine for each patient.</p>
                <div class="flex justify-end gap-2"><button type="button" class="btn btn-secondary" x-on:click="$dispatch('close-modal', 'save-template')">Cancel</button><button class="btn btn-primary">Save template</button></div>
            </form>
        </x-modal>
    @endcan
</x-layouts.app>
