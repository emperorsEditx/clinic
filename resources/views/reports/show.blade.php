@php
    $cols = $report->columns();
    $q = array_filter($filters, fn ($v) => $v !== null && $v !== '');
@endphp
<x-layouts.app :title="$report->title()">
    <x-page-header :title="$report->title()" :subtitle="$report->description()" :back="route('reports.index')">
        <x-slot:actions>
            <a href="{{ route('reports.export', [$report->key(), 'print'] + $q) }}" target="_blank" class="btn btn-secondary"><x-icon name="printer" /> Print</a>
            <a href="{{ route('reports.export', [$report->key(), 'pdf'] + $q) }}" class="btn btn-secondary"><x-icon name="download" /> PDF</a>
            <a href="{{ route('reports.export', [$report->key(), 'csv'] + $q) }}" class="btn btn-secondary">CSV</a>
            <a href="{{ route('reports.export', [$report->key(), 'xlsx'] + $q) }}" class="btn btn-primary"><x-icon name="download" /> Excel</a>
        </x-slot:actions>
    </x-page-header>

    <div class="card">
        <form method="GET" class="grid grid-cols-1 gap-3 border-b border-slate-100 p-4 sm:grid-cols-2 lg:grid-cols-6" aria-label="Report filters">
            <x-field name="from" label="From" type="date" :value="$filters['from']" />
            <x-field name="to" label="To" type="date" :value="$filters['to']" />
            @if (in_array('doctor', $report->filters(), true))
                <x-field name="doctor" label="Doctor" type="select" :options="$doctors->pluck('name', 'id')" :value="$filters['doctor'] ?? null" empty="All doctors" />
            @endif
            @if (in_array('status', $report->filters(), true))
                <x-field name="status" label="Status" type="select" :options="$report->statusOptions()" :value="$filters['status'] ?? null" empty="Any" />
            @endif
            @if (in_array('gender', $report->filters(), true))
                <x-field name="gender" label="Gender" type="select" :options="App\Enums\Gender::options()" :value="$filters['gender'] ?? null" empty="Any" />
            @endif
            @if (in_array('patient', $report->filters(), true))
                <div class="sm:col-span-2"><x-patient-picker name="patient" :patient="$patient" label="Patient" :required="false" /></div>
            @endif
            <div class="flex items-end gap-2 sm:col-span-2 lg:col-span-6">
                <button class="btn btn-secondary"><x-icon name="funnel" /> Run report</button>
                <div class="ml-2 flex flex-wrap gap-1 text-xs">
                    @foreach (['This month' => [now()->startOfMonth(), now()], 'Last month' => [now()->subMonthNoOverflow()->startOfMonth(), now()->subMonthNoOverflow()->endOfMonth()], 'Last 90 days' => [now()->subDays(89), now()], 'This year' => [now()->startOfYear(), now()]] as $label => [$f, $t])
                        <a href="{{ route('reports.show', [$report->key(), 'from' => $f->toDateString(), 'to' => $t->toDateString()] + \Illuminate\Support\Arr::except($q, ['from', 'to'])) }}" class="rounded-full border border-slate-200 px-2.5 py-1 text-slate-600 hover:border-brand-300 hover:text-brand-700">{{ $label }}</a>
                    @endforeach
                </div>
            </div>
        </form>

        <div class="flex items-center justify-between border-b border-slate-100 px-4 py-2 text-xs text-slate-500"><span>{{ fmt_date($filters['from']) }} – {{ fmt_date($filters['to']) }}</span><span>{{ number_format($rows->total()) }} row(s)</span></div>

        @if ($rows->isEmpty())
            <x-empty icon="chart" title="No data for this period">Try a wider date range or fewer filters.</x-empty>
        @else
            <div class="overflow-x-auto"><table class="table">
                <thead><tr>@foreach ($cols as $key => $heading)<th>{{ $heading }}</th>@endforeach</tr></thead>
                <tbody>
                @foreach ($rows as $row)
                    <tr>@foreach ($cols as $key => $heading)<td class="{{ is_numeric($row[$key] ?? null) ? 'tabular-nums' : '' }}">{{ $row[$key] ?? '—' }}</td>@endforeach</tr>
                @endforeach
                </tbody>
                @if ($totals)
                    <tfoot><tr class="bg-slate-50 font-semibold">@foreach ($cols as $key => $heading)<td class="border-t border-slate-200 px-4 py-2.5 tabular-nums">{{ $totals[$key] ?? '' }}</td>@endforeach</tr></tfoot>
                @endif
            </table></div>
            {{ $rows->links() }}
        @endif
    </div>
</x-layouts.app>
