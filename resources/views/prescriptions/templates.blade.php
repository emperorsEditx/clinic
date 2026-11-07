<x-layouts.app title="Prescription templates">
    <x-page-header title="Prescription templates" subtitle="Reusable starting points. Save a template from any finalised prescription; apply it in the prescription editor." :back="route('prescriptions.index')" />
    <div class="card">
        @forelse ($templates as $t)
            <div class="flex items-start justify-between gap-4 border-b border-slate-100 px-5 py-4 last:border-0">
                <div class="min-w-0">
                    <p class="text-[13px] font-medium">{{ $t->name }} @if($t->is_shared)<x-badge color="teal">Shared</x-badge>@endif</p>
                    <p class="text-xs text-slate-500">By {{ $t->doctor?->name ?? '—' }} · {{ count($t->items) }} medicine(s)</p>
                    <ul class="mt-1 text-xs text-slate-600">@foreach ($t->items as $i)<li>{{ $i['medicine_name'] ?? '' }} {{ $i['strength'] ?? '' }} — {{ $i['dosage'] ?? '' }}, {{ $i['frequency'] ?? '' }}, {{ $i['duration_days'] ?? '' }}d</li>@endforeach</ul>
                </div>
                @if ($t->doctor_id === auth()->id())
                    <form method="POST" action="{{ route('prescription-templates.destroy', $t) }}" data-confirm="Prescriptions created from this template are not affected." data-confirm-title="Delete template?" data-confirm-label="Delete" data-confirm-danger>@csrf @method('DELETE')<button class="btn btn-danger-ghost btn-sm" aria-label="Delete {{ $t->name }}"><x-icon name="trash" /></button></form>
                @endif
            </div>
        @empty
            <x-empty icon="document" title="No templates yet">Open a finalised prescription and choose “Save as template”.</x-empty>
        @endforelse
    </div>
</x-layouts.app>
