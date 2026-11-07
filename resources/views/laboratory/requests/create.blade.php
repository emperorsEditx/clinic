<x-layouts.app title="New laboratory request">
    <x-page-header title="New laboratory request" subtitle="Tests can also be requested directly from the consultation workspace." :back="route('laboratory.requests.index')" />
    <form method="POST" action="{{ route('laboratory.requests.store') }}" class="max-w-3xl" x-data="{ test: '{{ old('laboratory_test_id') }}' }">
        @csrf
        <div class="card"><div class="card-body grid grid-cols-1 gap-5 md:grid-cols-2">
            <div class="md:col-span-2"><x-patient-picker :patient="$patient" /></div>
            <div class="md:col-span-2">
                <label class="label" for="laboratory_test_id">Test from catalogue</label>
                <select id="laboratory_test_id" name="laboratory_test_id" class="input" x-model="test">
                    <option value="">— Custom test —</option>
                    @foreach ($tests->groupBy('category') as $cat => $group)
                        <optgroup label="{{ $cat }}">@foreach ($group as $t)<option value="{{ $t->id }}">{{ $t->name }}{{ $t->sample_type ? ' ('.$t->sample_type.')' : '' }}</option>@endforeach</optgroup>
                    @endforeach
                </select>
            </div>
            <div x-show="!test" class="md:col-span-2 grid grid-cols-1 gap-5 md:grid-cols-2">
                <x-field name="test_name" label="Custom test name" />
                <x-field name="test_category" label="Category" />
            </div>
            <x-field name="priority" label="Priority" type="select" :options="App\Enums\LabPriority::options()" value="routine" :empty="false" required />
            <x-field name="laboratory_name" label="External laboratory" placeholder="Leave blank for in-house" />
            <x-field class="md:col-span-2" name="clinical_notes" label="Clinical notes for the laboratory" type="textarea" rows="2" />
        </div></div>
        <div class="mt-6 flex justify-end gap-2"><a href="{{ route('laboratory.requests.index') }}" class="btn btn-secondary">Cancel</a><button class="btn btn-primary">Create request</button></div>
    </form>
</x-layouts.app>
