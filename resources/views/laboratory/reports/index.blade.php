<x-layouts.app title="Laboratory reports">
    <x-page-header title="Laboratory">
        <x-slot:actions>@can('create', App\Models\LaboratoryReport::class)<a href="{{ route('laboratory.reports.create') }}" class="btn btn-primary"><x-icon name="upload" /> Upload report</a>@endcan</x-slot:actions>
    </x-page-header>
    @include('laboratory._nav')

    <div class="card">
        <form method="GET" class="grid grid-cols-1 gap-3 border-b border-slate-100 p-4 sm:grid-cols-2 lg:grid-cols-5" aria-label="Filter reports">
            <div class="lg:col-span-2"><label class="label" for="q">Search</label><input id="q" name="q" value="{{ $filters['q'] ?? '' }}" class="input" placeholder="Patient name, ID, or test name"></div>
            <x-field name="status" label="Status" type="select" :options="App\Enums\LabReportStatus::options()" :value="$filters['status'] ?? null" empty="Any" />
            <x-field name="from" label="Report date from" type="date" :value="$filters['from'] ?? null" />
            <x-field name="to" label="to" type="date" :value="$filters['to'] ?? null" />
            <div class="flex gap-2 sm:col-span-2 lg:col-span-5"><button class="btn btn-secondary"><x-icon name="funnel" /> Apply</button>@if(array_filter($filters))<a href="{{ route('laboratory.reports.index') }}" class="btn btn-ghost">Reset</a>@endif</div>
        </form>
        @if ($reports->isEmpty())
            <x-empty icon="document" title="No reports found" />
        @else
            <div class="overflow-x-auto"><table class="table">
                <thead><tr><th>Report date</th><th>Report</th><th>Patient</th><th>Test</th><th>Uploaded by</th><th>Status</th><th class="text-right">File</th></tr></thead>
                <tbody>
                @foreach ($reports as $r)
                    <tr>
                        <td class="whitespace-nowrap">{{ fmt_date($r->report_date) }}</td>
                        <td><a href="{{ route('laboratory.reports.show', $r) }}" class="font-mono text-xs font-medium text-brand-700 hover:underline">{{ $r->report_number }}</a></td>
                        <td><span class="font-medium">{{ $r->patient->full_name }}</span><div class="text-xs text-slate-500">{{ $r->patient->patient_code }}</div></td>
                        <td>{{ $r->test_name }}@if($r->laboratory_name)<div class="text-xs text-slate-500">{{ $r->laboratory_name }}</div>@endif</td>
                        <td class="text-slate-600">{{ $r->uploader->name }}</td>
                        <td><x-badge :status="$r->status" />@if($r->reviewer)<div class="text-[11px] text-slate-500">{{ $r->reviewer->name }}</div>@endif</td>
                        <td class="whitespace-nowrap text-right">
                            @if ($r->isPreviewable())<a href="{{ route('laboratory.reports.download', [$r, 'inline' => 1]) }}" target="_blank" class="btn btn-ghost btn-sm" aria-label="Preview {{ $r->report_number }}"><x-icon name="eye" /></a>@endif
                            <a href="{{ route('laboratory.reports.download', $r) }}" class="btn btn-ghost btn-sm" aria-label="Download {{ $r->report_number }}"><x-icon name="download" /></a>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table></div>
            {{ $reports->links() }}
        @endif
    </div>
</x-layouts.app>
