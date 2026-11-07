@props(['label', 'value', 'icon' => 'chart', 'url' => null, 'hint' => null])
<a href="{{ $url ?? '#' }}" class="card group flex items-start gap-4 p-4 transition hover:border-brand-300 hover:shadow-sm">
    <div class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-brand-700"><x-icon :name="$icon" class="size-5" /></div>
    <div class="min-w-0">
        <p class="text-xs font-medium text-slate-500">{{ $label }}</p>
        <p class="mt-0.5 text-2xl font-semibold tabular-nums tracking-tight text-slate-900">{{ number_format($value) }}</p>
        @if ($hint)<p class="mt-0.5 truncate text-[11px] text-slate-400">{{ $hint }}</p>@endif
    </div>
</a>
