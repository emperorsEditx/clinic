@php $canManage = auth()->user()->can('manageHistory', $patient); @endphp
<datalist id="freq-list">@foreach (config('clinic.frequencies') as $f)<option value="{{ $f }}">@endforeach</datalist>

<div class="space-y-6">
    <section class="card">
        <div class="card-header">
            <div><h2 class="card-title">Clinic-prescribed medication courses</h2><p class="text-xs text-slate-500">Continuation is tracked per medicine from the prescription date and duration.</p></div>
            @can('repeat', [App\Models\Prescription::class, $patient])@unless($patient->trashed())<a href="{{ route('prescriptions.repeat', $patient) }}" class="btn btn-secondary btn-sm"><x-icon name="duplicate" /> Repeat medication</a>@endunless @endcan
        </div>
        @if ($patient->medicationCourses->isEmpty())
            <x-empty icon="capsule" title="No clinic-prescribed medications" compact />
        @else
            <div class="overflow-x-auto"><table class="table">
                <thead><tr><th>Medicine</th><th>Start</th><th>Days</th><th>Expected end</th><th>Continuation</th><th>Prescription</th><th>Status</th><th></th></tr></thead>
                <tbody>
                @foreach ($patient->medicationCourses as $course)
                    @php $left = $course->daysRemaining(); @endphp
                    <tr>
                        <td class="font-medium">{{ $course->medicine_name }}</td>
                        <td class="whitespace-nowrap">{{ fmt_date($course->start_date) }}</td>
                        <td class="tabular-nums">{{ $course->duration_days }}</td>
                        <td class="whitespace-nowrap">{{ fmt_date($course->expected_end_date) }}
                            @if ($course->status === App\Enums\MedicationCourseStatus::Active && $left !== null)<div class="text-xs {{ $left <= 2 ? 'text-amber-700' : 'text-slate-500' }}">{{ $left > 0 ? $left.' day(s) left' : ($left === 0 ? 'Ends today' : 'Overdue for review') }}</div>@endif
                        </td>
                        <td class="text-slate-600">{{ $course->continuation_instructions ?? '—' }}</td>
                        <td><a href="{{ route('prescriptions.show', $course->prescription_id) }}" class="font-mono text-xs text-brand-700 hover:underline">{{ $course->prescription->prescription_number }}</a></td>
                        <td><x-badge :status="$course->status" />@if($course->status_reason)<div class="mt-0.5 text-[11px] text-slate-500">{{ $course->status_reason }}</div>@endif</td>
                        <td class="text-right">@include('patients._course-status', ['course' => $course])</td>
                    </tr>
                @endforeach
                </tbody>
            </table></div>
        @endif
    </section>

    <section class="card">
        <div class="card-header">
            <div><h2 class="card-title">Other / intercurrent medication</h2><p class="text-xs text-slate-500">Medicines from other prescribers or sources — not prescribed by this clinic.</p></div>
            @if ($canManage)<button type="button" class="btn btn-secondary btn-sm" x-data x-on:click="$dispatch('open-modal', 'add-medication')"><x-icon name="plus" /> Add medication</button>@endif
        </div>
        @if ($patient->otherMedications->isEmpty())
            <x-empty icon="pill" title="No other medications recorded" compact />
        @else
            <div class="overflow-x-auto"><table class="table">
                <thead><tr><th>Medicine</th><th>Dosage</th><th>Frequency</th><th>Period</th><th>Source</th><th>Status</th><th></th></tr></thead>
                <tbody>
                @foreach ($patient->otherMedications as $m)
                    <tr>
                        <td><span class="font-medium">{{ $m->medicine_name }}</span> <x-badge color="orange">Other source</x-badge>@if($m->notes)<div class="text-xs text-slate-500">{{ $m->notes }}</div>@endif</td>
                        <td>{{ $m->dosage ?? '—' }}</td>
                        <td>{{ $m->frequency ?? '—' }}</td>
                        <td class="whitespace-nowrap text-slate-600">{{ fmt_date($m->start_date) }} – {{ fmt_date($m->end_date, 'ongoing') }}</td>
                        <td class="text-slate-600">{{ $m->prescribing_source ?? '—' }}</td>
                        <td><x-badge :status="$m->status" /></td>
                        <td>@include('patients._history-actions', ['type' => 'medication', 'entry' => $m, 'label' => $m->medicine_name])</td>
                    </tr>
                @endforeach
                </tbody>
            </table></div>
        @endif
    </section>

    <section class="card">
        <div class="card-header"><h2 class="card-title">Medication repetitions</h2></div>
        @if ($repetitions->isEmpty())
            <x-empty icon="arrow-path" title="No repeated medication" compact />
        @else
            <div class="overflow-x-auto"><table class="table">
                <thead><tr><th>Date</th><th>Medicine</th><th>From</th><th>New prescription</th><th>Reason</th><th>Authorised by</th></tr></thead>
                <tbody>
                @foreach ($repetitions as $r)
                    <tr>
                        <td class="whitespace-nowrap">{{ fmt_date($r->created_at) }}</td>
                        <td class="font-medium">{{ $r->newItem->medicine_name }} {{ $r->newItem->strength }}</td>
                        <td><a href="{{ route('prescriptions.show', $r->source_prescription_id) }}" class="font-mono text-xs text-brand-700 hover:underline">{{ $r->sourcePrescription->prescription_number }}</a></td>
                        <td><a href="{{ route('prescriptions.show', $r->new_prescription_id) }}" class="font-mono text-xs text-brand-700 hover:underline">{{ $r->newPrescription->prescription_number }}</a></td>
                        <td class="text-slate-600">{{ $r->reason }}</td>
                        <td>{{ $r->authorizer->name }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table></div>
        @endif
    </section>
</div>

@if ($canManage)
    <x-modal name="add-medication" title="Add other / intercurrent medication">
        <form method="POST" action="{{ route('patients.history.store', [$patient, 'medication']) }}">
            @csrf
            @include('patients._history-fields', ['type' => 'medication', 'entry' => null])
            <div class="mt-5 flex justify-end gap-2"><button type="button" class="btn btn-secondary" x-on:click="$dispatch('close-modal', 'add-medication')">Cancel</button><button class="btn btn-primary">Save</button></div>
        </form>
    </x-modal>
@endif
