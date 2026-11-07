@php
    $label = match ($type) {
        'condition' => $model->name, 'allergy' => $model->allergen, 'medication' => $model->medicine_name, default => $model->title ?: 'Clinical note',
    };
@endphp
<x-layouts.app :title="'Change log · '.$label">
    <x-page-header :title="'Change log: '.$label" :subtitle="$patient->full_name.' · '.$patient->patient_code" :back="route('patients.show', [$patient, 'tab' => $type === 'medication' ? 'medications' : 'history'])">
        <x-slot:meta>@if ($model->trashed())<div class="mt-2"><x-badge color="slate">Removed from active record</x-badge></div>@endif</x-slot:meta>
    </x-page-header>

    <section class="card">
        @forelse ($logs as $log)
            <div class="border-b border-slate-100 px-5 py-4 last:border-0">
                <div class="flex flex-wrap items-center gap-2">
                    <x-badge :color="match ($log->action) { 'created' => 'green', 'deleted', 'removed' => 'red', default => 'blue' }">{{ ucfirst(str_replace('_', ' ', $log->action)) }}</x-badge>
                    <span class="text-[13px] font-medium">{{ $log->user?->name ?? 'System' }}</span>
                    <span class="text-xs text-slate-500">{{ fmt_datetime($log->created_at) }}</span>
                </div>
                @if ($log->description)<p class="mt-1 text-[13px] text-slate-600">{{ $log->description }}</p>@endif
                <x-changes :changes="$log->changes" class="mt-2" />
            </div>
        @empty
            <x-empty icon="clock" title="No changes recorded" />
        @endforelse
    </section>
</x-layouts.app>
