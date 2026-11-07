<div class="space-y-6">
    <section class="card">
        <div class="card-header"><h2 class="card-title">Laboratory reports</h2>
            @can('create', App\Models\LaboratoryReport::class)<a href="{{ route('laboratory.reports.create', ['patient' => $patient->id]) }}" class="btn btn-secondary btn-sm"><x-icon name="upload" /> Upload report</a>@endcan
        </div>
        @if ($labReports->isEmpty())
            <x-empty icon="beaker" title="No laboratory reports" compact />
        @else
            <div class="overflow-x-auto"><table class="table">
                <thead><tr><th>Report date</th><th>Test</th><th>Laboratory</th><th>Status</th><th>Reviewed</th><th class="text-right">File</th></tr></thead>
                <tbody>
                @foreach ($labReports as $r)
                    <tr>
                        <td class="whitespace-nowrap">{{ fmt_date($r->report_date) }}</td>
                        <td><a href="{{ route('laboratory.reports.show', $r) }}" class="font-medium hover:text-brand-700">{{ $r->test_name }}</a><div class="font-mono text-[11px] text-slate-500">{{ $r->report_number }}</div></td>
                        <td class="text-slate-600">{{ $r->laboratory_name ?? '—' }}</td>
                        <td><x-badge :status="$r->status" /></td>
                        <td class="text-slate-600">{{ $r->reviewer ? $r->reviewer->name.' · '.fmt_date($r->reviewed_at) : '—' }}</td>
                        <td class="whitespace-nowrap text-right">
                            @if ($r->isPreviewable())<a href="{{ route('laboratory.reports.download', [$r, 'inline' => 1]) }}" target="_blank" class="btn btn-ghost btn-sm" aria-label="Preview"><x-icon name="eye" /></a>@endif
                            <a href="{{ route('laboratory.reports.download', $r) }}" class="btn btn-ghost btn-sm" aria-label="Download"><x-icon name="download" /></a>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table></div>
        @endif
    </section>

    <section class="card">
        <div class="card-header"><h2 class="card-title">Laboratory requests</h2>
            @can('create', App\Models\LaboratoryRequest::class)<a href="{{ route('laboratory.requests.create', ['patient' => $patient->id]) }}" class="btn btn-secondary btn-sm"><x-icon name="plus" /> Request test</a>@endcan
        </div>
        @if ($labRequests->isEmpty())
            <x-empty icon="beaker" title="No laboratory requests" compact />
        @else
            <div class="overflow-x-auto"><table class="table">
                <thead><tr><th>Requested</th><th>Test</th><th>Priority</th><th>Requested by</th><th>Status</th></tr></thead>
                <tbody>
                @foreach ($labRequests as $r)
                    <tr>
                        <td class="whitespace-nowrap">{{ fmt_date($r->created_at) }}</td>
                        <td><a href="{{ route('laboratory.requests.show', $r) }}" class="font-medium hover:text-brand-700">{{ $r->test_name }}</a><div class="font-mono text-[11px] text-slate-500">{{ $r->request_number }}</div></td>
                        <td><x-badge :status="$r->priority" /></td>
                        <td class="text-slate-600">{{ $r->requester->name }}</td>
                        <td><x-badge :status="$r->status" /></td>
                    </tr>
                @endforeach
                </tbody>
            </table></div>
        @endif
    </section>
</div>
