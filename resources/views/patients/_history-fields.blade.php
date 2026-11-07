{{-- Fields for one medical history entry. $type: condition|allergy|medication|note ; $entry: model|null --}}
@php $e = $entry ?? null; $sfx = $e ? '_'.$type.'_'.$e->id : '_new_'.$type; @endphp
@if ($type === 'condition')
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2" x-data="{ cat: @js($e?->category?->value ?? ($defaultCategory ?? 'condition')) }">
        <x-field name="category" label="Type" type="select" :options="App\Enums\ConditionCategory::options()" :value="$e?->category ?? ($defaultCategory ?? 'condition')" :empty="false" required x-model="cat" :id="'cat'.$sfx" />
        <x-field name="status" label="Status" type="select" :options="App\Enums\ConditionStatus::options()" :value="$e?->status ?? 'current'" :empty="false" required :id="'st'.$sfx" />
        <x-field class="sm:col-span-2" name="name" label="Condition / disease / procedure" :value="$e?->name" required :id="'nm'.$sfx" />
        <x-field name="onset_date" label="Onset / procedure date" type="date" :value="$e?->onset_date" :id="'on'.$sfx" />
        <x-field name="resolved_date" label="Resolved date" type="date" :value="$e?->resolved_date" :id="'rs'.$sfx" />
        <div x-show="cat === 'family_history'" class="sm:col-span-2"><x-field name="relation" label="Family member" :value="$e?->relation" placeholder="e.g. Father" :id="'rl'.$sfx" /></div>
        <x-field class="sm:col-span-2" name="notes" label="Notes" type="textarea" rows="2" :value="$e?->notes" :id="'nt'.$sfx" />
    </div>
@elseif ($type === 'allergy')
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <x-field class="sm:col-span-2" name="allergen" label="Allergen" :value="$e?->allergen" required placeholder="Drug, food or substance" :id="'al'.$sfx" />
        <x-field name="allergy_type" label="Type" type="select" :options="App\Enums\AllergyType::options()" :value="$e?->allergy_type ?? 'drug'" :empty="false" required :id="'at'.$sfx" />
        <x-field name="severity" label="Severity" type="select" :options="App\Enums\AllergySeverity::options()" :value="$e?->severity ?? 'unknown'" :empty="false" required :id="'sv'.$sfx" />
        <x-field name="reaction" label="Reaction" :value="$e?->reaction" :id="'re'.$sfx" />
        <x-field name="status" label="Status" type="select" :options="App\Enums\RecordStatus::options()" :value="$e?->status ?? 'active'" :empty="false" required :id="'as'.$sfx" />
        <x-field class="sm:col-span-2" name="notes" label="Notes" type="textarea" rows="2" :value="$e?->notes" :id="'an'.$sfx" />
    </div>
@elseif ($type === 'medication')
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <x-field class="sm:col-span-2" name="medicine_name" label="Medicine" :value="$e?->medicine_name" required :id="'mn'.$sfx" />
        <x-field name="dosage" label="Dosage" :value="$e?->dosage" :id="'md'.$sfx" />
        <x-field name="frequency" label="Frequency" :value="$e?->frequency" :id="'mf'.$sfx" list="freq-list" />
        <x-field name="start_date" label="Start date" type="date" :value="$e?->start_date" :id="'ms'.$sfx" />
        <x-field name="end_date" label="End date" type="date" :value="$e?->end_date" :id="'me'.$sfx" />
        <x-field name="prescribing_source" label="Prescribed by / source" :value="$e?->prescribing_source" placeholder="Other doctor, hospital, self (OTC)…" :id="'mp'.$sfx" />
        <x-field name="status" label="Status" type="select" :options="App\Enums\PatientMedicationStatus::options()" :value="$e?->status ?? 'current'" :empty="false" required :id="'mst'.$sfx" />
        <x-field class="sm:col-span-2" name="notes" label="Notes" type="textarea" rows="2" :value="$e?->notes" :id="'mno'.$sfx" />
    </div>
@else
    <div class="space-y-4">
        <x-field name="title" label="Title" :value="$e?->title" placeholder="Optional" :id="'tt'.$sfx" />
        <x-field name="notes" label="Note" type="textarea" rows="5" :value="$e?->notes" required :id="'tn'.$sfx" />
    </div>
@endif
