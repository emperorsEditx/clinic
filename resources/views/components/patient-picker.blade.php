{{-- Searchable patient dropdown that submits patient_id. --}}
@props(['name' => 'patient_id', 'patient' => null, 'label' => 'Patient', 'required' => true])
@php
    $selected = $patient ? ['id' => $patient->id, 'code' => $patient->patient_code, 'name' => $patient->full_name, 'meta' => $patient->phone] : null;
@endphp
<div x-data="patientSearch({ url: '{{ route('patients.search') }}', selected: @js($selected) })" class="relative" x-on:click.outside="open = false">
    <label class="label" for="pp_{{ $name }}">{{ $label }} @if($required)<span class="text-red-500">*</span>@endif</label>
    <input type="hidden" name="{{ $name }}" :value="selected ? selected.id : ''">
    <template x-if="selected">
        <div class="flex items-center justify-between rounded-lg border border-brand-200 bg-brand-50/60 px-3 py-2">
            <div class="min-w-0"><p class="truncate text-sm font-medium text-slate-900" x-text="selected.name"></p><p class="text-xs text-slate-500"><span x-text="selected.code"></span> <span x-show="selected.meta" x-text="'· ' + selected.meta"></span></p></div>
            <button type="button" class="btn btn-ghost btn-sm" x-on:click="clear()">Change</button>
        </div>
    </template>
    <div x-show="!selected">
        <div class="relative">
            <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400" />
            <input id="pp_{{ $name }}" x-ref="input" type="text" class="input pl-9 @error($name) input-error @enderror" placeholder="Search by name, patient ID or phone…" autocomplete="off"
                   x-model="query" x-on:input="search()" x-on:keydown.down.prevent="move(1)" x-on:keydown.up.prevent="move(-1)" x-on:keydown.enter.prevent="choose(results[active])" role="combobox" :aria-expanded="open">
            <span x-show="loading" class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-slate-400">Searching…</span>
        </div>
        <div x-show="open" x-cloak class="absolute z-30 mt-1 w-full overflow-hidden rounded-lg border border-slate-200 bg-white shadow-lg" role="listbox">
            <template x-for="(r, i) in results" :key="r.id">
                <button type="button" class="block w-full px-3 py-2 text-left hover:bg-slate-50" :class="i === active && 'bg-slate-50'" x-on:click="choose(r)" role="option">
                    <span class="block text-sm font-medium text-slate-900" x-text="r.name"></span>
                    <span class="block text-xs text-slate-500" x-text="r.code + (r.meta ? ' · ' + r.meta : '')"></span>
                </button>
            </template>
            <p x-show="!results.length && !error" class="px-3 py-3 text-xs text-slate-500">No matching patients. @can('patients.create')<a href="{{ route('patients.create') }}" class="font-medium text-brand-700 hover:underline">Register a new patient</a>@endcan</p>
            <p x-show="error" class="px-3 py-3 text-xs text-red-600" x-text="error"></p>
        </div>
    </div>
    @error($name)<p class="error">{{ $message }}</p>@enderror
</div>
