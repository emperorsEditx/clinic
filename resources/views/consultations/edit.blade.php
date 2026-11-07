@php
    $v = $c->vitals;
    $items = old('items', $draftRx ? $draftRx->items->map(fn ($i) => $i->only(App\Models\PrescriptionItem::FIELDS))->all() : []);
    $activeAllergies = $patient->allergies;
    $labRows = collect(old('lab_tests', []))->map(fn ($r) => $r + ['_key' => uniqid()])->values();
@endphp
<x-layouts.app :title="'Consultation · '.$patient->full_name">
    <x-page-header :title="'Consultation — '.$patient->full_name" :back="route('visits.show', $c->visit_id)">
        <x-slot:meta>
            <div class="mt-1.5 flex flex-wrap items-center gap-x-3 gap-y-1 text-[13px] text-slate-500">
                <a href="{{ route('patients.show', $patient) }}" class="font-mono text-xs font-medium text-brand-700 hover:underline">{{ $patient->patient_code }}</a>
                <span>{{ $patient->gender?->label() }}, {{ $patient->currentAge() ?? '—' }} y</span>
                @if ($patient->blood_group)<span>{{ $patient->blood_group }}</span>@endif
                <span>{{ $c->consultation_number }} · Visit {{ $c->visit->visit_number }}</span>
                <x-badge :status="$c->status" />
            </div>
        </x-slot:meta>
        <x-slot:actions>
            <form method="POST" action="{{ route('consultations.destroy', $c) }}" data-confirm="The draft and any unsaved prescription will be discarded. This cannot be undone." data-confirm-title="Discard draft?" data-confirm-label="Discard" data-confirm-danger>
                @csrf @method('DELETE')<button class="btn btn-ghost text-red-600">Discard draft</button>
            </form>
        </x-slot:actions>
    </x-page-header>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
        {{-- Clinical form --}}
        <form method="POST" action="{{ route('consultations.update', $c) }}" class="space-y-6 xl:col-span-2" id="consultation-form" x-data x-on:keydown.ctrl.s.window.prevent="$refs.save.click()" x-on:keydown.meta.s.window.prevent="$refs.save.click()">
            @csrf @method('PUT')

            <section class="card">
                <div class="card-header"><h2 class="card-title">1 · Complaints &amp; symptoms</h2></div>
                <div class="card-body space-y-4">
                    @if ($c->visit->chief_complaint)<p class="text-xs text-slate-500">Chief complaint at reception: <span class="font-medium text-slate-700">{{ $c->visit->chief_complaint }}</span></p>@endif
                    <x-field name="symptoms" label="Presenting complaints and symptoms" type="textarea" rows="3" :value="$c->symptoms" />
                </div>
            </section>

            <section class="card">
                <div class="card-header"><h2 class="card-title">2 · Vital signs &amp; observations</h2><span class="text-xs text-slate-500">All optional</span></div>
                <div class="card-body space-y-4">
                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                        <div>
                            <label class="label" for="bp_s">Blood pressure</label>
                            <div class="flex items-center gap-1"><input id="bp_s" type="number" name="vitals[systolic_bp]" value="{{ old('vitals.systolic_bp', $v?->systolic_bp) }}" class="input px-2 @error('vitals.systolic_bp') input-error @enderror" placeholder="Sys" aria-label="Systolic"><span class="text-slate-400">/</span><input type="number" name="vitals[diastolic_bp]" value="{{ old('vitals.diastolic_bp', $v?->diastolic_bp) }}" class="input px-2 @error('vitals.diastolic_bp') input-error @enderror" placeholder="Dia" aria-label="Diastolic"></div>
                            <p class="help">mmHg</p>
                        </div>
                        <x-field name="vitals[pulse_rate]" label="Pulse" type="number" :value="$v?->pulse_rate" help="bpm" />
                        <x-field name="vitals[temperature]" label="Temperature" type="number" step="0.1" :value="$v?->temperature" help="°C" />
                        <x-field name="vitals[oxygen_saturation]" label="SpO₂" type="number" :value="$v?->oxygen_saturation" help="%" />
                        <x-field name="vitals[respiratory_rate]" label="Resp. rate" type="number" :value="$v?->respiratory_rate" help="/min" />
                        <x-field name="vitals[weight]" label="Weight" type="number" step="0.01" :value="$v?->weight" help="kg" />
                        <x-field name="vitals[height]" label="Height" type="number" step="0.1" :value="$v?->height" help="cm" />
                        @if ($v?->bmi())<div><p class="label">BMI</p><p class="py-2 text-sm font-medium">{{ $v->bmi() }}</p></div>@endif
                    </div>
                    <x-field name="clinical_observations" label="Clinical observations / examination findings" type="textarea" rows="3" :value="$c->clinical_observations" />
                </div>
            </section>

            <section class="card">
                <div class="card-header"><h2 class="card-title">3 · Diagnosis &amp; plan</h2></div>
                <div class="card-body space-y-4">
                    <x-field name="diagnosis" label="Diagnosis" type="textarea" rows="2" :value="$c->diagnosis" help="Required to complete the consultation." />
                    <x-field name="treatment_plan" label="Treatment plan" type="textarea" rows="3" :value="$c->treatment_plan" />
                </div>
            </section>

            <section class="card">
                <div class="card-header"><h2 class="card-title">4 · Prescription</h2>@if($draftRx)<span class="font-mono text-xs text-slate-500">{{ $draftRx->prescription_number }} · draft</span>@endif</div>
                <div class="card-body space-y-4">
                    @include('prescriptions._builder', ['items' => $items, 'templates' => $templates, 'allergies' => $activeAllergies])
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div x-data x-on:template-instructions.window="if (!$refs.gi.value) $refs.gi.value = $event.detail">
                            <label class="label" for="general_instructions">General instructions</label>
                            <textarea x-ref="gi" id="general_instructions" name="general_instructions" rows="2" class="input">{{ old('general_instructions', $draftRx?->general_instructions) }}</textarea>
                        </div>
                        <x-field name="prescription_notes" label="Prescription notes" type="textarea" rows="2" :value="$draftRx?->notes" help="Printed on the prescription." />
                    </div>
                    <p class="text-xs text-slate-500">The prescription is finalised automatically when you complete the consultation, and medication courses start from today.</p>
                </div>
            </section>

            <section class="card">
                <div class="card-header"><h2 class="card-title">5 · Laboratory tests</h2></div>
                <div class="card-body space-y-3" x-data="rows(@js($labRows))">
                    @if ($c->laboratoryRequests->isNotEmpty())
                        <ul class="space-y-1 text-[13px]">
                            @foreach ($c->laboratoryRequests as $r)
                                <li class="flex items-center justify-between rounded-lg bg-slate-50 px-3 py-2"><span><a href="{{ route('laboratory.requests.show', $r) }}" class="font-medium hover:text-brand-700">{{ $r->test_name }}</a> <span class="text-xs text-slate-500">{{ $r->request_number }}</span></span><span class="flex gap-1"><x-badge :status="$r->priority" /><x-badge :status="$r->status" /></span></li>
                            @endforeach
                        </ul>
                    @endif
                    <template x-for="(row, i) in rows" :key="row._key">
                        <div class="grid grid-cols-1 gap-2 rounded-xl border border-slate-200 p-3 md:grid-cols-12">
                            <div class="md:col-span-4"><label class="label text-xs" :for="`lt_${i}`">Catalogue test</label>
                                <select :id="`lt_${i}`" class="input" :name="`lab_tests[${i}][laboratory_test_id]`" x-model="row.laboratory_test_id">
                                    <option value="">— Custom test —</option>
                                    @foreach ($labTests->groupBy('category') as $cat => $tests)
                                        <optgroup label="{{ $cat }}">@foreach ($tests as $t)<option value="{{ $t->id }}">{{ $t->name }}</option>@endforeach</optgroup>
                                    @endforeach
                                </select>
                            </div>
                            <div class="md:col-span-3" x-show="!row.laboratory_test_id"><label class="label text-xs" :for="`ln_${i}`">Custom test name</label><input :id="`ln_${i}`" class="input" :name="`lab_tests[${i}][test_name]`" x-model="row.test_name"></div>
                            <div class="md:col-span-2"><label class="label text-xs" :for="`lp_${i}`">Priority</label>
                                <select :id="`lp_${i}`" class="input" :name="`lab_tests[${i}][priority]`" x-model="row.priority">@foreach (App\Enums\LabPriority::options() as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select></div>
                            <div class="md:col-span-2" :class="row.laboratory_test_id && 'md:col-span-5'"><label class="label text-xs" :for="`lc_${i}`">Clinical notes</label><input :id="`lc_${i}`" class="input" :name="`lab_tests[${i}][clinical_notes]`" x-model="row.clinical_notes"></div>
                            <div class="flex items-end md:col-span-1"><button type="button" class="btn btn-danger-ghost btn-sm" x-on:click="remove(i)" aria-label="Remove test"><x-icon name="trash" /></button></div>
                        </div>
                    </template>
                    <button type="button" class="btn btn-secondary btn-sm" x-on:click="add({ laboratory_test_id: '', test_name: '', priority: 'routine', clinical_notes: '' })"><x-icon name="plus" /> Request a test</button>
                    <p class="text-xs text-slate-500">New requests are sent to the laboratory as soon as you save.</p>
                </div>
            </section>

            <section class="card">
                <div class="card-header"><h2 class="card-title">6 · Follow-up &amp; notes</h2></div>
                <div class="card-body grid grid-cols-1 gap-4 md:grid-cols-3">
                    <x-field name="follow_up_date" label="Follow-up date" type="date" :value="$c->follow_up_date" :min="today()->addDay()->toDateString()" />
                    <x-field class="md:col-span-2" name="follow_up_instructions" label="Follow-up instructions" type="textarea" rows="2" :value="$c->follow_up_instructions" />
                    <x-field class="md:col-span-3" name="doctor_notes" label="Doctor's private notes" type="textarea" rows="2" :value="$c->doctor_notes" help="Not printed on the prescription." />
                </div>
            </section>

            <div class="sticky bottom-0 z-20 -mx-4 flex items-center justify-end gap-2 border-t border-slate-200 bg-white/95 px-4 py-3 backdrop-blur sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
                <p class="mr-auto hidden text-xs text-slate-500 sm:block">Draft last saved {{ $c->updated_at->diffForHumans() }} · Ctrl+S to save</p>
                <button x-ref="save" type="submit" name="action" value="save" class="btn btn-secondary">Save draft</button>
                <button type="button" class="btn btn-primary" x-on:click="$dispatch('open-modal', 'complete-consultation')"><x-icon name="check" /> Complete consultation</button>
            </div>

            <x-modal name="complete-consultation" title="Complete consultation?">
                <p class="text-[13px] text-slate-600">Completing will lock the clinical record (later corrections are tracked as amendments), finalise the prescription, start medication courses and schedule the follow-up.</p>
                <div class="mt-5 flex justify-end gap-2"><button type="button" class="btn btn-secondary" x-on:click="$dispatch('close-modal', 'complete-consultation')">Keep editing</button><button type="submit" name="action" value="complete" class="btn btn-primary">Complete</button></div>
            </x-modal>
        </form>

        {{-- Patient context --}}
        <aside class="space-y-4 xl:sticky xl:top-20 xl:self-start" aria-label="Patient summary">
            <section class="card">
                <div class="card-header"><h2 class="card-title">Allergies</h2></div>
                <div class="card-body text-[13px]">
                    @forelse ($activeAllergies as $a)<p class="mb-1"><span class="font-medium text-red-700">{{ $a->allergen }}</span> <span class="text-xs text-slate-500">{{ $a->allergy_type->label() }} · {{ $a->severity->label() }}{{ $a->reaction ? ' · '.$a->reaction : '' }}</span></p>@empty<p class="text-slate-400">No known allergies recorded</p>@endforelse
                </div>
            </section>
            <section class="card">
                <div class="card-header"><h2 class="card-title">Medical history</h2><a href="{{ route('patients.show', [$patient, 'tab' => 'history']) }}" target="_blank" class="text-xs font-medium text-brand-700 hover:underline">Open</a></div>
                <div class="card-body space-y-1 text-[13px]">
                    @forelse ($patient->conditions as $cond)<p>{{ $cond->name }} <span class="text-xs text-slate-500">{{ $cond->category->label() }}{{ $cond->status === App\Enums\ConditionStatus::Historical ? ' · historical' : '' }}{{ $cond->relation ? ' · '.$cond->relation : '' }}</span></p>@empty<p class="text-slate-400">Nothing recorded</p>@endforelse
                </div>
            </section>
            <section class="card">
                <div class="card-header"><h2 class="card-title">Current medications</h2></div>
                <div class="card-body space-y-1 text-[13px]">
                    @foreach ($patient->medicationCourses as $m)<p>{{ $m->medicine_name }} <span class="text-xs text-slate-500">until {{ fmt_date($m->expected_end_date) }}</span></p>@endforeach
                    @foreach ($patient->otherMedications as $m)<p>{{ $m->medicine_name }} <span class="text-xs text-slate-500">{{ $m->dosage }} {{ $m->frequency }}</span> <x-badge color="orange">Other</x-badge></p>@endforeach
                    @if ($patient->medicationCourses->isEmpty() && $patient->otherMedications->isEmpty())<p class="text-slate-400">None recorded</p>@endif
                </div>
            </section>
            <section class="card">
                <div class="card-header"><h2 class="card-title">Previous consultations</h2></div>
                @forelse ($previousConsultations as $pc)
                    <details class="border-b border-slate-100 px-5 py-3 last:border-0">
                        <summary class="cursor-pointer text-[13px]"><span class="font-medium">{{ fmt_date($pc->completed_at) }}</span> <span class="text-slate-500">· {{ \Illuminate\Support\Str::limit($pc->diagnosis, 40) }}</span></summary>
                        <dl class="mt-2 space-y-1 text-xs text-slate-700">
                            <p><span class="text-slate-500">Doctor:</span> {{ $pc->doctor->name }}</p>
                            @if ($pc->symptoms)<p><span class="text-slate-500">Symptoms:</span> {{ $pc->symptoms }}</p>@endif
                            <p><span class="text-slate-500">Diagnosis:</span> {{ $pc->diagnosis }}</p>
                            @if ($pc->treatment_plan)<p><span class="text-slate-500">Plan:</span> {{ $pc->treatment_plan }}</p>@endif
                            @if ($pc->vitals && ! $pc->vitals->isEmpty())<p>@include('consultations._vitals-inline', ['v' => $pc->vitals])</p>@endif
                            <a href="{{ route('consultations.show', $pc) }}" target="_blank" class="text-brand-700 hover:underline">Open full record</a>
                        </dl>
                    </details>
                @empty
                    <x-empty icon="stethoscope" title="First consultation" compact />
                @endforelse
            </section>
            <section class="card">
                <div class="card-header"><h2 class="card-title">Previous prescriptions</h2></div>
                @forelse ($previousPrescriptions as $rx)
                    @php $copy = $rx->items->map(fn ($i) => $i->toTemplateArray())->values(); @endphp
                    <div class="border-b border-slate-100 px-5 py-3 last:border-0">
                        <div class="flex items-center justify-between gap-2"><a href="{{ route('prescriptions.show', $rx) }}" target="_blank" class="text-[13px] font-medium hover:text-brand-700">{{ fmt_date($rx->prescription_date) }} · {{ $rx->prescription_number }}</a>
                            <button type="button" class="btn btn-ghost btn-sm" x-data x-on:click="$dispatch('add-medicines', @js($copy))" title="Copy medicines into this prescription">Copy</button></div>
                        <ul class="mt-1 text-xs text-slate-600">@foreach ($rx->items as $i)<li>{{ $i->medicine_name }} {{ $i->strength }} — {{ $i->dosage }}, {{ $i->frequency }}, {{ $i->duration_days }}d</li>@endforeach</ul>
                    </div>
                @empty
                    <x-empty icon="document" title="No previous prescriptions" compact />
                @endforelse
            </section>
            <section class="card">
                <div class="card-header"><h2 class="card-title">Laboratory reports</h2></div>
                @forelse ($previousReports as $r)
                    <a href="{{ route('laboratory.reports.show', $r) }}" target="_blank" class="flex items-center justify-between gap-2 border-b border-slate-100 px-5 py-2.5 last:border-0 hover:bg-slate-50"><span class="text-[13px]">{{ $r->test_name }} <span class="text-xs text-slate-500">{{ fmt_date($r->report_date) }}</span></span><x-badge :status="$r->status" /></a>
                @empty
                    <x-empty icon="beaker" title="No reports" compact />
                @endforelse
            </section>
        </aside>
    </div>
</x-layouts.app>
