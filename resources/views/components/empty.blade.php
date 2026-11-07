@props(['icon' => 'info', 'title' => 'Nothing here yet', 'compact' => false])
<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center text-center '.($compact ? 'py-6 px-4' : 'py-12 px-6')]) }}>
    <div class="mb-3 flex size-10 items-center justify-center rounded-full bg-slate-100 text-slate-400"><x-icon :name="$icon" class="size-5" /></div>
    <p class="text-[13px] font-medium text-slate-700">{{ $title }}</p>
    @if (trim($slot))<div class="mt-1 max-w-sm text-xs text-slate-500">{{ $slot }}</div>@endif
</div>
