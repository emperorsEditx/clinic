<x-layouts.app title="Visits">
    <x-page-header title="Visits" subtitle="Every patient encounter is recorded as a separate visit.">
        <x-slot:actions>@can('create', App\Models\Visit::class)<a href="{{ route('visits.create') }}" class="btn btn-primary"><x-icon name="plus" /> Record visit</a>@endcan</x-slot:actions>
    </x-page-header>

    <div class="card">
        <form method="GET" class="grid grid-cols-1 gap-3 border-b border-slate-100 p-4 sm:grid-cols-2 lg:grid-cols-6" aria-label="Filter visits">
            <div class="lg:col-span-2"><label class="label" for="q">Search</label><input id="q" name="q" value="{{ $filters['q'] ?? '' }}" class="input" placeholder="Visit number, patient name, ID or phone"></div>
            <x-field name="status" label="Status" type="select" :options="App\Enums\VisitStatus::options()" :value="$filters['status'] ?? null" empty="Any" />
            <x-field name="doctor" label="Doctor" type="select" :options="$doctors->pluck('name', 'id')" :value="$filters['doctor'] ?? null" empty="Any" />
            <x-field name="from" label="From" type="date" :value="$filters['from'] ?? null" />
            <x-field name="to" label="To" type="date" :value="$filters['to'] ?? null" />
            <div class="flex gap-2 sm:col-span-2 lg:col-span-6"><button class="btn btn-secondary"><x-icon name="funnel" /> Apply</button>@if(array_filter($filters))<a href="{{ route('visits.index') }}" class="btn btn-ghost">Reset</a>@endif</div>
        </form>
        @if ($visits->isEmpty())
            <x-empty icon="clipboard" title="No visits found" />
        @else
            <div class="overflow-x-auto"><table class="table">
                <thead><tr><th>Date</th><th>Visit</th><th>Patient</th><th>Doctor</th><th>Type</th><th>Chief complaint</th><th>Status</th></tr></thead>
                <tbody>
                @foreach ($visits as $v)
                    <tr class="row-link" onclick="if (!event.target.closest('a,button')) window.location='{{ route('visits.show', $v) }}'">
                        <td class="whitespace-nowrap">{{ fmt_datetime($v->visit_at) }}</td>
                        <td><a href="{{ route('visits.show', $v) }}" class="font-mono text-xs font-medium text-brand-700 hover:underline">{{ $v->visit_number }}</a></td>
                        <td><a href="{{ route('patients.show', $v->patient) }}" class="font-medium hover:text-brand-700">{{ $v->patient->full_name }}</a><div class="text-xs text-slate-500">{{ $v->patient->patient_code }}</div></td>
                        <td class="text-slate-600">{{ $v->doctor->name }}</td>
                        <td class="text-slate-600">{{ $v->visitTypeLabel() }}</td>
                        <td class="text-slate-600">{{ $v->chief_complaint ?? '—' }}</td>
                        <td><x-badge :status="$v->status" /></td>
                    </tr>
                @endforeach
                </tbody>
            </table></div>
            {{ $visits->links() }}
        @endif
    </div>
</x-layouts.app>
