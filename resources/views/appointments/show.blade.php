@php $a = $appointment; @endphp
<x-layouts.app :title="$a->appointment_number">
    <x-page-header :title="'Appointment '.$a->appointment_number" :back="route('appointments.index')">
        <x-slot:meta><div class="mt-2"><x-badge :status="$a->status" /></div></x-slot:meta>
        <x-slot:actions>
            @can('checkIn', $a)
                <form method="POST" action="{{ route('appointments.check-in', $a) }}">@csrf<button class="btn btn-primary"><x-icon name="check" /> Check in</button></form>
            @endcan
            @if ($a->isOpen())@can('update', $a)<a href="{{ route('appointments.edit', $a) }}" class="btn btn-secondary"><x-icon name="pencil" /> Reschedule</a>@endcan @endif
            @if ($a->visit)<a href="{{ route('visits.show', $a->visit) }}" class="btn btn-secondary">Open visit {{ $a->visit->visit_number }}</a>@endif
        </x-slot:actions>
    </x-page-header>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <section class="card lg:col-span-2">
            <div class="card-header"><h2 class="card-title">Details</h2></div>
            <div class="card-body">
                <dl class="dl">
                    <dt>Patient</dt><dd><a href="{{ route('patients.show', $a->patient) }}" class="font-medium text-brand-700 hover:underline">{{ $a->patient->full_name }}</a> <span class="text-slate-500">{{ $a->patient->patient_code }}</span></dd>
                    <dt>Doctor</dt><dd>{{ $a->doctor->displayName() }}</dd>
                    <dt>Date &amp; time</dt><dd>{{ fmt_date($a->appointment_date) }}, {{ fmt_time($a->scheduledAt()) }}</dd>
                    <dt>Type</dt><dd>{{ $a->appointment_type }}</dd>
                    <dt>Reason</dt><dd>{{ $a->reason ?? '—' }}</dd>
                    <dt>Notes</dt><dd class="whitespace-pre-line">{{ $a->notes ?? '—' }}</dd>
                    @if ($a->checked_in_at)<dt>Checked in</dt><dd>{{ fmt_datetime($a->checked_in_at) }}</dd>@endif
                    @if ($a->cancellation_reason)<dt>Status reason</dt><dd>{{ $a->cancellation_reason }}</dd>@endif
                </dl>
            </div>
        </section>

        @can('update', $a)
            @if ($a->allowedTransitions())
                <section class="card">
                    <div class="card-header"><h2 class="card-title">Update status</h2></div>
                    <form method="POST" action="{{ route('appointments.status', $a) }}" class="card-body space-y-3" x-data="{ s: '' }">
                        @csrf
                        <div class="space-y-1.5">
                            @foreach ($a->allowedTransitions() as $t)
                                <label class="flex items-center gap-2 text-[13px]"><input type="radio" name="status" value="{{ $t->value }}" x-model="s" class="border-slate-300 text-brand-600"> {{ $t->label() }}</label>
                            @endforeach
                        </div>
                        <div x-show="s === 'cancelled' || s === 'no_show'" x-cloak><label class="label" for="reason">Reason</label><input id="reason" name="reason" class="input" maxlength="255"></div>
                        <button class="btn btn-primary w-full" :disabled="!s">Update status</button>
                    </form>
                </section>
            @endif
        @endcan
    </div>
</x-layouts.app>
