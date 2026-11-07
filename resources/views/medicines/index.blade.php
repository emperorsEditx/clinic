<x-layouts.app title="Medicine catalogue">
    <x-page-header title="Medicine catalogue" subtitle="Searchable list used when prescribing. Doctors can always enter a custom medicine.">
        <x-slot:actions><button type="button" class="btn btn-primary" x-data x-on:click="$dispatch('open-modal', 'add-medicine')"><x-icon name="plus" /> Add medicine</button></x-slot:actions>
    </x-page-header>
    <div class="card">
        <form method="GET" class="flex flex-wrap items-end gap-3 border-b border-slate-100 p-4">
            <div class="w-full sm:w-72"><label class="label" for="q">Search</label><input id="q" name="q" value="{{ request('q') }}" class="input" placeholder="Brand or generic name"></div>
            <x-field name="status" label="Status" type="select" :options="['active' => 'Active', 'inactive' => 'Inactive']" :value="request('status', 'active')" :empty="false" />
            <button class="btn btn-secondary"><x-icon name="funnel" /> Apply</button>
        </form>
        @if ($medicines->isEmpty())
            <x-empty icon="capsule" title="No medicines">Add the medicines your clinic prescribes most often.</x-empty>
        @else
            <div class="overflow-x-auto"><table class="table">
                <thead><tr><th>Name</th><th>Generic</th><th>Form</th><th>Strength</th><th>Default route</th><th>Manufacturer</th><th></th></tr></thead>
                <tbody>
                @foreach ($medicines as $m)
                    <tr>
                        <td class="font-medium">{{ $m->name }} @unless($m->is_active)<x-badge color="slate">Inactive</x-badge>@endunless</td>
                        <td class="text-slate-600">{{ $m->generic_name ?? '—' }}</td>
                        <td>{{ $m->form ?? '—' }}</td>
                        <td>{{ $m->strength ?? '—' }}</td>
                        <td>{{ $m->default_route ?? '—' }}</td>
                        <td class="text-slate-600">{{ $m->manufacturer ?? '—' }}</td>
                        <td class="text-right">
                            <button type="button" class="btn btn-ghost btn-sm" x-data x-on:click="$dispatch('open-modal', 'edit-med-{{ $m->id }}')" aria-label="Edit {{ $m->name }}"><x-icon name="pencil" /></button>
                            <x-modal :name="'edit-med-'.$m->id" title="Edit medicine">
                                <form method="POST" action="{{ route('medicines.update', $m) }}" class="grid grid-cols-1 gap-4 text-left sm:grid-cols-2">
                                    @csrf @method('PUT')
                                    @include('medicines._fields', ['m' => $m, 'sfx' => $m->id])
                                    <label class="flex items-center gap-2 text-[13px] sm:col-span-2"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" @checked($m->is_active) class="rounded border-slate-300"> Active (shown when prescribing)</label>
                                    <div class="flex justify-end gap-2 sm:col-span-2"><button type="button" class="btn btn-secondary" x-on:click="$dispatch('close-modal', 'edit-med-{{ $m->id }}')">Cancel</button><button class="btn btn-primary">Save</button></div>
                                </form>
                            </x-modal>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table></div>
            {{ $medicines->links() }}
        @endif
    </div>

    <x-modal name="add-medicine" title="Add medicine" :show="$errors->any() && ! old('_method')">
        <form method="POST" action="{{ route('medicines.store') }}" class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            @csrf
            @include('medicines._fields', ['m' => null, 'sfx' => 'new'])
            <div class="flex justify-end gap-2 sm:col-span-2"><button type="button" class="btn btn-secondary" x-on:click="$dispatch('close-modal', 'add-medicine')">Cancel</button><button class="btn btn-primary">Add medicine</button></div>
        </form>
    </x-modal>
</x-layouts.app>
