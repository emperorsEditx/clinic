<x-layouts.app title="Appointments">
    <x-page-header title="Appointments" subtitle="Schedule, confirm and check in patients.">
        <x-slot:actions>@can('create', App\Models\Appointment::class)<a href="{{ route('appointments.create') }}" class="btn btn-primary"><x-icon name="plus" /> Book appointment</a>@endcan</x-slot:actions>
    </x-page-header>

    <div class="card">
        <form method="GET" class="grid grid-cols-1 gap-3 border-b border-slate-100 p-4 sm:grid-cols-2 lg:grid-cols-6" aria-label="Filter appointments">
            <div class="lg:col-span-2"><label class="label" for="q">Patient</label><input id="q" name="q" value="{{ $filters['q'] ?? '' }}" class="input" placeholder="Name, patient ID or phone"></div>
            <x-field name="status" label="Status" type="select" :options="App\Enums\AppointmentStatus::options()" :value="$filters['status'] ?? null" empty="Any" />
            <x-field name="doctor" label="Doctor" type="select" :options="$doctors->pluck('name', 'id')" :value="$filters['doctor'] ?? null" empty="Any" />
            <x-field name="from" label="From" type="date" :value="$filters['from'] ?? null" />
            <x-field name="to" label="To" type="date" :value="$filters['to'] ?? null" />
            <div class="flex gap-2 sm:col-span-2 lg:col-span-6"><button class="btn btn-secondary"><x-icon name="funnel" /> Apply</button><a href="{{ route('appointments.index') }}" class="btn btn-ghost">Today onwards</a></div>
        </form>

        @if ($appointments->isEmpty())
            <x-empty icon="calendar" title="No appointments found">Adjust the date range or filters.</x-empty>
        @else
            <div class="overflow-x-auto"><table class="table">
                <thead><tr><th>Date &amp; time</th><th>Patient</th><th>Doctor</th><th>Type / reason</th><th>Status</th><th class="text-right"><span class="sr-only">Actions</span></th></tr></thead>
                <tbody>
                @foreach ($appointments as $a)
                    <tr>
                        <td class="whitespace-nowrap"><a href="{{ route('appointments.show', $a) }}" class="font-medium hover:text-brand-700">{{ fmt_date($a->appointment_date) }}</a><div class="text-xs tabular-nums text-slate-500">{{ fmt_time($a->scheduledAt()) }} · <span class="font-mono">{{ $a->appointment_number }}</span></div></td>
                        <td><a href="{{ route('patients.show', $a->patient) }}" class="font-medium text-slate-900 hover:text-brand-700">{{ $a->patient->full_name }}</a><div class="text-xs text-slate-500">{{ $a->patient->patient_code }}{{ $a->patient->phone ? ' · '.$a->patient->phone : '' }}</div></td>
                        <td class="text-slate-600">{{ $a->doctor->name }}</td>
                        <td class="text-slate-600">{{ $a->appointment_type }}@if($a->reason)<div class="text-xs text-slate-500">{{ $a->reason }}</div>@endif</td>
                        <td><x-badge :status="$a->status" /></td>
                        <td class="whitespace-nowrap text-right">
                            @can('checkIn', $a)
                                <form method="POST" action="{{ route('appointments.check-in', $a) }}" class="inline">@csrf<button class="btn btn-secondary btn-sm">Check in</button></form>
                            @endcan
                            @if ($a->visit)<a href="{{ route('visits.show', $a->visit->id) }}" class="btn btn-ghost btn-sm">Visit</a>@endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table></div>
            {{ $appointments->links() }}
        @endif
    </div>
</x-layouts.app>
