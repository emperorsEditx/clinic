@php $items = old('items', $rx->items->map(fn ($i) => $i->only(App\Models\PrescriptionItem::FIELDS))->all()); @endphp
<x-layouts.app :title="'Edit '.$rx->prescription_number">
    <x-page-header :title="($rx->previous_version_id ? 'Amend prescription' : 'Repeat prescription draft').' '.$rx->prescription_number" :subtitle="$rx->patient->full_name.' · '.$rx->patient->patient_code" :back="$rx->previousVersion ? route('prescriptions.show', $rx->previousVersion) : route('patients.show', [$rx->patient, 'tab' => 'prescriptions'])">
        <x-slot:actions>
            <form method="POST" action="{{ route('prescriptions.destroy', $rx) }}" data-confirm="This draft is deleted. The original prescription is not affected." data-confirm-title="Discard draft?" data-confirm-label="Discard" data-confirm-danger>@csrf @method('DELETE')<button class="btn btn-ghost text-red-600">Discard draft</button></form>
        </x-slot:actions>
    </x-page-header>

    @if ($rx->previousVersion)
        <div class="mb-4 rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-[13px] text-blue-900">Amending <a href="{{ route('prescriptions.show', $rx->previousVersion) }}" class="font-medium underline">{{ $rx->previousVersion->prescription_number }}</a> — reason: {{ $rx->amendment_reason }}. When finalised, the previous version is marked superseded and its active medication courses are replaced.</div>
    @endif

    <form method="POST" action="{{ route('prescriptions.update', $rx) }}" class="space-y-6">
        @csrf @method('PUT')
        <section class="card"><div class="card-body space-y-4">
            @include('prescriptions._builder', ['items' => $items, 'templates' => $templates, 'allergies' => $rx->patient->allergies])
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div x-data x-on:template-instructions.window="if (!$refs.gi.value) $refs.gi.value = $event.detail"><label class="label" for="general_instructions">General instructions</label><textarea x-ref="gi" id="general_instructions" name="general_instructions" rows="2" class="input">{{ old('general_instructions', $rx->general_instructions) }}</textarea></div>
                <x-field name="notes" label="Notes" type="textarea" rows="2" :value="$rx->notes" />
            </div>
        </div></section>
        <div class="flex justify-end gap-2">
            <button class="btn btn-secondary" name="finalize" value="0">Save draft</button>
            <button class="btn btn-primary" name="finalize" value="1"><x-icon name="check" /> Finalise prescription</button>
        </div>
    </form>
</x-layouts.app>
