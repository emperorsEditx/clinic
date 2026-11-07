<x-layouts.app :title="'Repeat prescription · '.$patient->full_name">
    <x-page-header title="Repeat prescription" :subtitle="$patient->full_name.' · '.$patient->patient_code" :back="route('patients.show', [$patient, 'tab' => 'medications'])" />

    @if ($prescriptions->isEmpty())
        <div class="card"><x-empty icon="document" title="No finalised prescriptions to repeat">Repeat prescriptions are created from a patient's earlier finalised prescriptions.</x-empty></div>
    @else
        <div class="mb-4 flex items-start gap-2 rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-[13px] text-blue-900"><x-icon name="info" class="mt-0.5 size-4 shrink-0" /><p>Nothing is repeated automatically. Select the medicines to repeat, review the dosage and duration, and record why. The new prescription links to the original and records you as the authorising doctor.</p></div>

        <form method="POST" action="{{ route('prescriptions.repeat.store', $patient) }}" x-data="{ src: '{{ old('source_prescription_id', $selected?->id) }}' }" class="space-y-6">
            @csrf
            <section class="card">
                <div class="card-header"><h2 class="card-title">1 · Choose the prescription to repeat</h2></div>
                <div class="divide-y divide-slate-100">
                    @foreach ($prescriptions as $rx)
                        <label class="flex cursor-pointer items-start gap-3 px-5 py-3 hover:bg-slate-50" :class="src == '{{ $rx->id }}' && 'bg-brand-50/60'">
                            <input type="radio" name="source_prescription_id" value="{{ $rx->id }}" x-model="src" class="mt-1 border-slate-300 text-brand-600">
                            <span class="min-w-0 flex-1"><span class="text-[13px] font-medium">{{ $rx->prescription_number }}</span> <span class="text-xs text-slate-500">{{ fmt_date($rx->prescription_date) }} · {{ $rx->doctor->name }}</span> <x-badge :status="$rx->status" />
                                <span class="block truncate text-xs text-slate-600">{{ $rx->items->map(fn ($i) => $i->medicine_name.' '.$i->strength)->implode(', ') }}</span></span>
                        </label>
                    @endforeach
                </div>
                @error('source_prescription_id')<p class="error px-5 pb-3">{{ $message }}</p>@enderror
            </section>

            <section class="card">
                <div class="card-header"><h2 class="card-title">2 · Select and adjust medicines</h2></div>
                @error('selected')<p class="error px-5 pt-3">{{ $message }}</p>@enderror
                @foreach ($prescriptions as $rx)
                    <fieldset x-show="src == '{{ $rx->id }}'" :disabled="src != '{{ $rx->id }}'" x-cloak class="divide-y divide-slate-100">
                        <legend class="sr-only">Medicines from {{ $rx->prescription_number }}</legend>
                        @foreach ($rx->items as $i)
                            <div class="px-5 py-4" x-data="{ on: {{ in_array($i->id, old('selected', [])) || ! old('selected') ? 'true' : 'false' }} }">
                                <label class="flex items-center gap-3">
                                    <input type="checkbox" name="selected[]" value="{{ $i->id }}" x-model="on" class="rounded border-slate-300 text-brand-600">
                                    <span class="text-[13px]"><span class="font-medium">{{ $i->medicine_name }}</span> {{ $i->strength }} <span class="text-slate-500">— {{ $i->dosage }}, {{ $i->frequency }}, {{ $i->duration_days }} days</span></span>
                                    @if ($i->course)<x-badge :status="$i->course->status" />@endif
                                </label>
                                <div x-show="on" class="mt-3 grid grid-cols-2 gap-2 pl-7 md:grid-cols-6">
                                    <div><label class="label text-xs" for="rd_{{ $i->id }}">Dosage</label><input id="rd_{{ $i->id }}" class="input" name="items[{{ $i->id }}][dosage]" value="{{ old("items.{$i->id}.dosage", $i->dosage) }}"></div>
                                    <div class="col-span-2 md:col-span-1"><label class="label text-xs" for="rf_{{ $i->id }}">Frequency</label><input id="rf_{{ $i->id }}" class="input" list="rx-frequencies" name="items[{{ $i->id }}][frequency]" value="{{ old("items.{$i->id}.frequency", $i->frequency) }}"></div>
                                    <div><label class="label text-xs" for="rt_{{ $i->id }}">Timing</label><input id="rt_{{ $i->id }}" class="input" list="rx-timings" name="items[{{ $i->id }}][timing]" value="{{ old("items.{$i->id}.timing", $i->timing) }}"></div>
                                    <div><label class="label text-xs" for="rdd_{{ $i->id }}">Duration (days)</label><input id="rdd_{{ $i->id }}" type="number" min="1" max="365" class="input" name="items[{{ $i->id }}][duration_days]" value="{{ old("items.{$i->id}.duration_days", $i->duration_days) }}"></div>
                                    <div><label class="label text-xs" for="rq_{{ $i->id }}">Quantity</label><input id="rq_{{ $i->id }}" class="input" name="items[{{ $i->id }}][quantity]" value="{{ old("items.{$i->id}.quantity", $i->quantity) }}"></div>
                                    <div class="col-span-2 md:col-span-6"><label class="label text-xs" for="ri_{{ $i->id }}">Instructions</label><input id="ri_{{ $i->id }}" class="input" name="items[{{ $i->id }}][instructions]" value="{{ old("items.{$i->id}.instructions", $i->instructions) }}"></div>
                                </div>
                            </div>
                        @endforeach
                    </fieldset>
                @endforeach
                <p x-show="!src" class="px-5 py-4 text-[13px] text-slate-500">Choose a prescription above.</p>
            </section>
            <datalist id="rx-frequencies">@foreach (config('clinic.frequencies') as $o)<option value="{{ $o }}">@endforeach</datalist>
            <datalist id="rx-timings">@foreach (config('clinic.timings') as $o)<option value="{{ $o }}">@endforeach</datalist>

            <section class="card">
                <div class="card-header"><h2 class="card-title">3 · Authorisation</h2></div>
                <div class="card-body grid grid-cols-1 gap-4 md:grid-cols-2">
                    <x-field class="md:col-span-2" name="reason" label="Reason for repetition" required placeholder="e.g. Stable on current therapy, continue for another month" />
                    <x-field name="consultation_id" label="Attach to consultation" type="select" :options="$drafts->mapWithKeys(fn ($d) => [$d->id => $d->consultation_number.' (draft, '.fmt_date($d->created_at).')'])" empty="Create a medication-review encounter" help="Without a draft consultation, a medication-review visit is recorded to document the authorisation." />
                    <div class="flex items-end"><p class="text-xs text-slate-500">Authorising doctor: <span class="font-medium text-slate-700">{{ auth()->user()->displayName() }}</span></p></div>
                </div>
            </section>

            <div class="flex justify-end gap-2"><a href="{{ route('patients.show', [$patient, 'tab' => 'medications']) }}" class="btn btn-secondary">Cancel</a><button class="btn btn-primary" :disabled="!src"><x-icon name="check" /> Authorise repeat prescription</button></div>
        </form>
    @endif
</x-layouts.app>
