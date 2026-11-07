@php $canUpdate = auth()->user()->can('appointments.manage') || auth()->user()->can('consultations.manage'); @endphp
<x-layouts.app title="Follow-ups">
    <x-page-header title="Follow-ups" subtitle="Follow-ups are scheduled from consultations and close automatically when the patient is seen again." />
    <div class="card">
        <form method="GET" class="grid grid-cols-1 gap-3 border-b border-slate-100 p-4 sm:grid-cols-2 lg:grid-cols-5" aria-label="Filter follow-ups">
            <x-field name="status" label="Status" type="select" :options="App\Enums\FollowUpStatus::options()" :value="$filters['status']" :empty="false" />
            <x-field name="doctor" label="Doctor" type="select" :options="$doctors->pluck('name', 'id')" :value="$filters['doctor'] ?? null" empty="Any" />
            <x-field name="from" label="From" type="date" :value="$filters['from'] ?? null" />
            <x-field name="to" label="To" type="date" :value="$filters['to'] ?? null" />
            <div class="flex items-end"><button class="btn btn-secondary"><x-icon name="funnel" /> Apply</button></div>
        </form>
        @if ($followUps->isEmpty())
            <x-empty icon="arrow-path" title="No follow-ups" />
        @else
            <div class="overflow-x-auto"><table class="table">
                <thead><tr><th>Due</th><th>Patient</th><th>Doctor</th><th>Instructions</th><th>Status</th><th class="text-right"><span class="sr-only">Actions</span></th></tr></thead>
                <tbody>
                @foreach ($followUps as $f)
                    @php $overdue = $f->status === App\Enums\FollowUpStatus::Pending && $f->follow_up_date->isPast() && ! $f->follow_up_date->isToday(); @endphp
                    <tr>
                        <td class="whitespace-nowrap {{ $overdue ? 'font-medium text-red-700' : '' }}">{{ fmt_date($f->follow_up_date) }}@if($overdue)<div class="text-xs">Overdue</div>@elseif($f->follow_up_date->isToday())<div class="text-xs text-amber-700">Today</div>@endif</td>
                        <td><a href="{{ route('patients.show', $f->patient) }}" class="font-medium hover:text-brand-700">{{ $f->patient->full_name }}</a><div class="text-xs text-slate-500">{{ $f->patient->patient_code }}{{ $f->patient->phone ? ' · '.$f->patient->phone : '' }}</div></td>
                        <td class="text-slate-600">{{ $f->doctor?->name ?? '—' }}</td>
                        <td class="text-slate-600">{{ $f->instructions ?? '—' }}</td>
                        <td><x-badge :status="$f->status" /></td>
                        <td class="whitespace-nowrap text-right">
                            @if ($f->status === App\Enums\FollowUpStatus::Pending)
                                @can('appointments.manage')<a href="{{ route('appointments.create', ['patient' => $f->patient_id]) }}" class="btn btn-secondary btn-sm">Book</a>@endcan
                                @if ($canUpdate)
                                    <form method="POST" action="{{ route('follow-ups.status', $f) }}" class="inline">@csrf<input type="hidden" name="status" value="missed"><button class="btn btn-ghost btn-sm">Missed</button></form>
                                    <form method="POST" action="{{ route('follow-ups.status', $f) }}" class="inline" data-confirm="The follow-up will no longer appear as due." data-confirm-title="Cancel follow-up?" data-confirm-label="Cancel follow-up">@csrf<input type="hidden" name="status" value="cancelled"><button class="btn btn-ghost btn-sm">Cancel</button></form>
                                @endif
                            @elseif ($canUpdate && in_array($f->status, [App\Enums\FollowUpStatus::Missed, App\Enums\FollowUpStatus::Cancelled], true))
                                <form method="POST" action="{{ route('follow-ups.status', $f) }}" class="inline">@csrf<input type="hidden" name="status" value="pending"><button class="btn btn-ghost btn-sm">Reopen</button></form>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table></div>
            {{ $followUps->links() }}
        @endif
    </div>
</x-layouts.app>
