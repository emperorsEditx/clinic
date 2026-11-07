@php $cols = $report->columns(); @endphp
<!DOCTYPE html>
<html><head><meta charset="utf-8"><title>{{ $report->title() }}</title>@include('pdf._styles')</head>
<body>
    <div class="footer"><table style="width:100%"><tr><td>{{ $branding['clinic_name'] }} · {{ $report->title() }}</td><td class="right">Generated {{ fmt_datetime(now()) }} by {{ auth()->user()?->name }}</td></tr></table></div>
    @include('pdf._header', ['docTitle' => $report->title(), 'docSubtitle' => fmt_date($filters['from']).' – '.fmt_date($filters['to'])])
    @include('reports._filters-summary')
    <table class="grid">
        <thead><tr>@foreach ($cols as $h)<th>{{ $h }}</th>@endforeach</tr></thead>
        <tbody>
        @forelse ($rows as $row)
            <tr>@foreach ($cols as $k => $h)<td>{{ $row[$k] ?? '—' }}</td>@endforeach</tr>
        @empty
            <tr><td colspan="{{ count($cols) }}" class="muted">No data for this period.</td></tr>
        @endforelse
        </tbody>
        @if ($totals)<tfoot><tr>@foreach ($cols as $k => $h)<td style="font-weight: bold; border-top: 1px solid #94a3b8">{{ $totals[$k] ?? '' }}</td>@endforeach</tr></tfoot>@endif
    </table>
    <p class="small muted" style="margin-top: 4px">{{ $rows->count() }} row(s)</p>
</body></html>
