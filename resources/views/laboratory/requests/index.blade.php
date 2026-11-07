<x-layouts.app title="Laboratory requests">
    <x-page-header title="Laboratory">
        <x-slot:actions>
            @can('create', App\Models\LaboratoryReport::class)<a href="{{ route('laboratory.reports.create') }}" class="btn btn-secondary"><x-icon name="upload" /> Upload report</a>@endcan
            @can('create', App\Models\LaboratoryRequest::class)<a href="{{ route('laboratory.requests.create') }}" class="btn btn-primary"><x-icon name="plus" /> New request</a>@endcan
        </x-slot:actions>
    </x-page-header>
    @include('laboratory._nav')

    <div class="card">
        <form method="GET" class="grid grid-cols-1 gap-3 border-b border-slate-100 p-4 sm:grid-cols-2 lg:grid-cols-5" aria-label="Filter requests">
            <div class="lg:col-span-2"><label class="label" for="q">Search</label><input id="q" name="q" value="{{ $filters['q'] ?? '' }}" class="input" placeholder="Test, request number or patient"></div>
            <x-field name="status" label="Status" type="select" :options="['pending' => 'Pending (all open)'] + App\Enums\LabRequestStatus::options()" :value="$filters['status'] ?? null" empty="Any" />
            <x-field name="from" label="From" type="date" :value="$filters['from'] ?? null" />
            <x-field name="to" label="To" type="date" :value="$filters['to'] ?? null" />
            <div class="flex gap-2 sm:col-span-2 lg:col-span-5"><button class="btn btn-secondary"><x-icon name="funnel" /> Apply</button>@if(array_filter($filters))<a href="{{ route('laboratory.requests.index') }}" class="btn btn-ghost">Reset</a>@endif</div>
        </form>
        @if ($requests->isEmpty())
            <x-empty icon="beaker" title="No laboratory requests" />
        @else
            <div class="overflow-x-auto"><table class="table">
                <thead><tr><th>Requested</th><th>Request</th><th>Patient</th><th>Test</th><th>Priority</th><th>Requested by</th><th>Reports</th><th>Status</th></tr></thead>
                <tbody>
                @foreach ($requests as $r)
                    <tr class="row-link" onclick="if (!event.target.closest('a')) window.location='{{ route('laboratory.requests.show', $r) }}'">
                        <td class="whitespace-nowrap">{{ fmt_datetime($r->created_at) }}</td>
                        <td><a href="{{ route('laboratory.requests.show', $r) }}" class="font-mono text-xs font-medium text-brand-700 hover:underline">{{ $r->request_number }}</a></td>
                        <td><span class="font-medium">{{ $r->patient->full_name }}</span><div class="text-xs text-slate-500">{{ $r->patient->patient_code }}</div></td>
                        <td>{{ $r->test_name }}@if($r->test_category)<div class="text-xs text-slate-500">{{ $r->test_category }}</div>@endif</td>
                        <td><x-badge :status="$r->priority" /></td>
                        <td class="text-slate-600">{{ $r->requester->name }}</td>
                        <td class="tabular-nums">{{ $r->reports_count }}</td>
                        <td><x-badge :status="$r->status" /></td>
                    </tr>
                @endforeach
                </tbody>
            </table></div>
            {{ $requests->links() }}
        @endif
    </div>
</x-layouts.app>
