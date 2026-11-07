{{-- <x-modal name="add-allergy" title="Add allergy"> ... </x-modal>; open with $dispatch('open-modal', 'add-allergy') --}}
@props(['name', 'title' => null, 'maxWidth' => 'max-w-lg', 'show' => false])
<div x-data="{ open: @js($show) }" x-on:open-modal.window="if ($event.detail === '{{ $name }}') open = true" x-on:close-modal.window="if ($event.detail === '{{ $name }}') open = false" x-on:keydown.escape.window="open = false" x-show="open" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true" @if($title) aria-label="{{ $title }}" @endif>
    <div class="fixed inset-0 bg-slate-900/40" x-on:click="open = false"></div>
    <div class="relative flex min-h-full items-start justify-center p-4 sm:items-center">
        <div x-trap.noscroll="open" class="card relative w-full {{ $maxWidth }} shadow-xl">
            @if ($title)
                <div class="card-header"><h2 class="card-title">{{ $title }}</h2><button type="button" class="btn btn-ghost btn-sm" x-on:click="open = false" aria-label="Close"><x-icon name="x" /></button></div>
            @endif
            <div class="card-body">{{ $slot }}</div>
        </div>
    </div>
</div>
