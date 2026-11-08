<x-layouts.app title="Patients">
    <x-page-header title="Patients" subtitle="Search and manage registered patients.">
        <x-slot:actions>
            @can('create', App\Models\Patient::class)<a href="{{ route('patients.create') }}" class="btn btn-primary"><x-icon name="user-plus" /> Register patient</a>@endcan
        </x-slot:actions>
    </x-page-header>

    <div class="card">
        <form method="GET" class="grid grid-cols-1 gap-3 border-b border-slate-100 p-4 sm:grid-cols-2 lg:grid-cols-8 xl:grid-cols-9" aria-label="Filter patients">
            <div class="lg:col-span-2 xl:col-span-3"><label class="label" for="q">Search</label><input id="q" name="q" value="{{ $filters['q'] ?? '' }}" class="input" placeholder="Name, patient ID or phone"></div>
            <x-field name="gender" label="Gender" type="select" :options="App\Enums\Gender::options()" :value="$filters['gender'] ?? null" empty="Any" />
            <x-field name="doctor" label="Seen by doctor" type="select" :options="$doctors->pluck('name', 'id')" :value="$filters['doctor'] ?? null" empty="Any" />
            <div class="min-w-0 lg:col-span-2"><label class="label">Registered between</label><div class="grid min-w-0 grid-cols-2 gap-1"><input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="input min-w-0 px-2" aria-label="Registered from"><input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="input min-w-0 px-2" aria-label="Registered to"></div></div>
            <div class="min-w-0 lg:col-span-2"><label class="label">Visited between</label><div class="grid min-w-0 grid-cols-2 gap-1"><input type="date" name="visit_from" value="{{ $filters['visit_from'] ?? '' }}" class="input min-w-0 px-2" aria-label="Visit from"><input type="date" name="visit_to" value="{{ $filters['visit_to'] ?? '' }}" class="input min-w-0 px-2" aria-label="Visit to"></div></div>
            <div class="flex items-end gap-2 sm:col-span-2 lg:col-span-8 xl:col-span-9">
                <button class="btn btn-secondary"><x-icon name="funnel" /> Apply filters</button>
                @if (array_filter($filters))<a href="{{ route('patients.index') }}" class="btn btn-ghost">Reset</a>@endif
                @can('patients.delete')
                    <label class="ml-auto flex items-center gap-2 text-xs text-slate-600"><input type="checkbox" name="archived" value="1" @checked(request()->boolean('archived')) onchange="this.form.submit()" class="rounded border-slate-300"> Show archived</label>
                @endcan
            </div>
        </form>

        @if ($patients->isEmpty())
            <x-empty icon="users" title="No patients found">{{ array_filter($filters) ? 'Try adjusting your search or filters.' : 'Registered patients will appear here.' }}</x-empty>
        @else
            <div class="overflow-x-auto">
                <table class="table">
                    <thead><tr><th>Patient ID</th><th>Name</th><th>Gender / Age</th><th>Phone</th><th>Registered</th><th>Last visit</th><th class="text-right"><span class="sr-only">Actions</span></th></tr></thead>
                    <tbody>
                    @foreach ($patients as $p)
                        <tr class="row-link" onclick="if (!event.target.closest('a,button,form')) window.location='{{ $p->trashed() ? '#' : route('patients.show', $p) }}'">
                            <td class="whitespace-nowrap font-mono text-xs text-slate-600">{{ $p->patient_code }}</td>
                            <td>@if($p->trashed())<span class="font-medium">{{ $p->full_name }}</span> <x-badge color="slate">Archived</x-badge>@else<a href="{{ route('patients.show', $p) }}" class="font-medium text-slate-900 hover:text-brand-700">{{ $p->full_name }}</a>@endif</td>
                            <td class="whitespace-nowrap text-slate-600">{{ $p->gender?->label() }} · {{ $p->currentAge() ?? '—' }}{{ $p->currentAge() !== null ? ' y' : '' }}</td>
                            <td class="whitespace-nowrap text-slate-600">{{ $p->phone ?? '—' }}</td>
                            <td class="whitespace-nowrap text-slate-600">{{ fmt_date($p->registration_date) }}</td>
                            <td class="whitespace-nowrap text-slate-600">{{ fmt_date($p->visits_max_visit_at) }}</td>
                            <td class="whitespace-nowrap text-right">
                                @if ($p->trashed())
                                    <form method="POST" action="{{ route('patients.restore', $p->id) }}">@csrf<button class="btn btn-secondary btn-sm">Restore</button></form>
                                @else
                                    @can('visits.manage')<a href="{{ route('visits.create', ['patient' => $p->id]) }}" class="btn btn-ghost btn-sm">New visit</a>@endcan
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            {{ $patients->links() }}
        @endif
    </div>
</x-layouts.app>
