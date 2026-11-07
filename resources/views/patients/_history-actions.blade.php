{{-- Edit / remove / change-log controls for a history entry. Needs $type, $entry, $patient, $label --}}
<div class="flex shrink-0 items-center gap-1 no-print">
    <a href="{{ route('patients.history.changes', [$patient, $type, $entry->id]) }}" class="btn btn-ghost btn-sm" title="Change log" aria-label="Change log for {{ $label }}"><x-icon name="clock" /></a>
    @can('manageHistory', $patient)
        <button type="button" class="btn btn-ghost btn-sm" x-data x-on:click="$dispatch('open-modal', 'edit-{{ $type }}-{{ $entry->id }}')" aria-label="Edit {{ $label }}"><x-icon name="pencil" /></button>
        <form method="POST" action="{{ route('patients.history.destroy', [$patient, $type, $entry->id]) }}" data-confirm="The entry will be removed from the active record but kept in the change log." data-confirm-title="Remove {{ $label }}?" data-confirm-reason="reason" data-confirm-label="Remove" data-confirm-danger>
            @csrf @method('DELETE')<button class="btn btn-danger-ghost btn-sm" aria-label="Remove {{ $label }}"><x-icon name="trash" /></button>
        </form>
        <x-modal :name="'edit-'.$type.'-'.$entry->id" title="Edit {{ $label }}">
            <form method="POST" action="{{ route('patients.history.update', [$patient, $type, $entry->id]) }}">
                @csrf @method('PUT')
                @include('patients._history-fields', ['type' => $type, 'entry' => $entry])
                <p class="mt-3 text-xs text-slate-500">The previous values are preserved in the change log.</p>
                <div class="mt-5 flex justify-end gap-2"><button type="button" class="btn btn-secondary" x-on:click="$dispatch('close-modal', 'edit-{{ $type }}-{{ $entry->id }}')">Cancel</button><button class="btn btn-primary">Save changes</button></div>
            </form>
        </x-modal>
    @endcan
</div>
