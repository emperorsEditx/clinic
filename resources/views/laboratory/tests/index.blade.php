@php $canManage = auth()->user()->can('lab_tests.manage'); @endphp
<x-layouts.app title="Laboratory test catalogue">
    <x-page-header title="Laboratory">
        <x-slot:actions>@if($canManage)<button type="button" class="btn btn-primary" x-data x-on:click="$dispatch('open-modal', 'add-test')"><x-icon name="plus" /> Add test</button>@endif</x-slot:actions>
    </x-page-header>
    @include('laboratory._nav')
    <datalist id="lab-categories">@foreach ($categories as $c)<option value="{{ $c }}">@endforeach</datalist>

    <div class="card">
        <form method="GET" class="flex items-end gap-3 border-b border-slate-100 p-4"><div class="w-full sm:w-72"><label class="label" for="q">Search</label><input id="q" name="q" value="{{ request('q') }}" class="input" placeholder="Test or category"></div><button class="btn btn-secondary">Search</button></form>
        @if ($tests->isEmpty())
            <x-empty icon="beaker" title="No tests in the catalogue" />
        @else
            <div class="overflow-x-auto"><table class="table">
                <thead><tr><th>Test</th><th>Code</th><th>Category</th><th>Sample</th><th>Status</th>@if($canManage)<th></th>@endif</tr></thead>
                <tbody>
                @foreach ($tests as $t)
                    <tr>
                        <td class="font-medium">{{ $t->name }}</td>
                        <td class="font-mono text-xs">{{ $t->code ?? '—' }}</td>
                        <td>{{ $t->category }}</td>
                        <td class="text-slate-600">{{ $t->sample_type ?? '—' }}</td>
                        <td>@if($t->is_active)<x-badge color="green">Active</x-badge>@else<x-badge color="slate">Inactive</x-badge>@endif</td>
                        @if ($canManage)
                            <td class="text-right">
                                <button type="button" class="btn btn-ghost btn-sm" x-data x-on:click="$dispatch('open-modal', 'edit-test-{{ $t->id }}')" aria-label="Edit {{ $t->name }}"><x-icon name="pencil" /></button>
                                <x-modal :name="'edit-test-'.$t->id" title="Edit test">
                                    <form method="POST" action="{{ route('laboratory.tests.update', $t) }}" class="grid grid-cols-1 gap-4 text-left sm:grid-cols-2">
                                        @csrf @method('PUT')
                                        @include('laboratory.tests._fields', ['t' => $t, 'sfx' => $t->id])
                                        <label class="flex items-center gap-2 text-[13px] sm:col-span-2"><input type="checkbox" name="is_active" value="1" @checked($t->is_active) class="rounded border-slate-300"> Active</label>
                                        <div class="flex justify-end gap-2 sm:col-span-2"><button type="button" class="btn btn-secondary" x-on:click="$dispatch('close-modal', 'edit-test-{{ $t->id }}')">Cancel</button><button class="btn btn-primary">Save</button></div>
                                    </form>
                                </x-modal>
                            </td>
                        @endif
                    </tr>
                @endforeach
                </tbody>
            </table></div>
            {{ $tests->links() }}
        @endif
    </div>

    @if ($canManage)
        <x-modal name="add-test" title="Add laboratory test" :show="$errors->any() && ! old('_method')">
            <form method="POST" action="{{ route('laboratory.tests.store') }}" class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                @csrf
                @include('laboratory.tests._fields', ['t' => null, 'sfx' => 'new'])
                <div class="flex justify-end gap-2 sm:col-span-2"><button type="button" class="btn btn-secondary" x-on:click="$dispatch('close-modal', 'add-test')">Cancel</button><button class="btn btn-primary">Add test</button></div>
            </form>
        </x-modal>
    @endif
</x-layouts.app>
