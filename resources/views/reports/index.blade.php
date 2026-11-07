<x-layouts.app title="Reports">
    <x-page-header title="Reports &amp; analytics" subtitle="Every report supports date ranges, filters, Excel/CSV/PDF export and a print view. Exports are recorded in the audit log." />
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
        @foreach ($reports as $r)
            <a href="{{ route('reports.show', $r->key()) }}" class="card group flex items-start gap-4 p-5 hover:border-brand-300 hover:shadow-sm">
                <div class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-brand-700"><x-icon name="chart" class="size-5" /></div>
                <div><p class="text-[13px] font-semibold text-slate-900 group-hover:text-brand-700">{{ $r->title() }}</p><p class="mt-0.5 text-xs text-slate-500">{{ $r->description() }}</p></div>
            </a>
        @endforeach
    </div>
</x-layouts.app>
