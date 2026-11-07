@php $cols = $report->columns(); @endphp
<x-layouts.print :title="$report->title()" :subtitle="fmt_date($filters['from']).' – '.fmt_date($filters['to'])" :landscape="count($cols) > 5">
    @include('reports._filters-summary')
    <table class="w-full border-collapse text-[11px]">
        <thead><tr class="border-b-2 border-slate-400 text-left">@foreach ($cols as $h)<th class="px-1.5 py-1">{{ $h }}</th>@endforeach</tr></thead>
        <tbody>
        @forelse ($rows as $row)
            <tr class="border-b border-slate-100">@foreach ($cols as $k => $h)<td class="px-1.5 py-1 align-top">{{ $row[$k] ?? '—' }}</td>@endforeach</tr>
        @empty
            <tr><td colspan="{{ count($cols) }}" class="py-3 text-center text-slate-500">No data for this period.</td></tr>
        @endforelse
        </tbody>
        @if ($totals)<tfoot><tr class="border-t-2 border-slate-400 font-semibold">@foreach ($cols as $k => $h)<td class="px-1.5 py-1">{{ $totals[$k] ?? '' }}</td>@endforeach</tr></tfoot>@endif
    </table>
    <p class="mt-2 text-[10px] text-slate-500">{{ $rows->count() }} row(s)</p>
</x-layouts.print>
