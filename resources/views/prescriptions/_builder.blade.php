{{--
    Medicine rows editor. Params: $items (array of item arrays), $templates (collection), $allergies (collection|null)
    Posts items[N][field]. Blank rows are stripped server-side.
--}}
@php
    $tplData = ($templates ?? collect())->map(fn ($t) => ['id' => $t->id, 'name' => $t->name, 'general_instructions' => $t->general_instructions, 'items' => $t->items])->values();
    $itemErrors = collect($errors->getMessages())->filter(fn ($m, $k) => str_starts_with($k, 'items'))->flatten()->unique();
@endphp
<datalist id="rx-routes">@foreach (config('clinic.routes') as $o)<option value="{{ $o }}">@endforeach</datalist>
<datalist id="rx-frequencies">@foreach (config('clinic.frequencies') as $o)<option value="{{ $o }}">@endforeach</datalist>
<datalist id="rx-timings">@foreach (config('clinic.timings') as $o)<option value="{{ $o }}">@endforeach</datalist>

<div x-data="prescriptionBuilder({ items: @js(array_values($items)), searchUrl: '{{ route('medicines.search') }}', templates: @js($tplData) })" x-on:add-medicines.window="$event.detail.forEach((i) => add(i))">
    @if ($itemErrors->isNotEmpty())
        <div class="mb-3 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-700" role="alert"><ul class="list-disc pl-4">@foreach ($itemErrors as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif

    @if (! empty($allergies) && $allergies->isNotEmpty())
        <div class="mb-3 flex items-start gap-2 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-800"><x-icon name="warning" class="mt-0.5 size-4 shrink-0" /><span><span class="font-semibold">Check allergies before prescribing:</span> {{ $allergies->pluck('allergen')->implode(', ') }}</span></div>
    @endif

    <div class="space-y-3">
        <template x-for="(item, index) in items" :key="item._key">
            <div class="rounded-xl border border-slate-200 bg-slate-50/50 p-3">
                <div class="mb-2 flex items-center justify-between">
                    <p class="text-xs font-semibold text-slate-500">Medicine <span x-text="index + 1"></span>
                        <span x-show="item.medicine_id" class="ml-1 badge badge-teal">Catalogue</span>
                        <span x-show="!item.medicine_id && item.medicine_name" class="ml-1 badge badge-slate">Custom</span></p>
                    <div class="flex items-center gap-1">
                        <button type="button" class="btn btn-ghost btn-sm" x-on:click="move(index, -1)" :disabled="index === 0" aria-label="Move up"><x-icon name="chevron-up" class="size-3.5" /></button>
                        <button type="button" class="btn btn-ghost btn-sm" x-on:click="move(index, 1)" :disabled="index === items.length - 1" aria-label="Move down"><x-icon name="chevron-down" class="size-3.5" /></button>
                        <button type="button" class="btn btn-danger-ghost btn-sm" x-on:click="remove(index)" aria-label="Remove medicine"><x-icon name="trash" /></button>
                    </div>
                </div>
                <input type="hidden" :name="`items[${index}][medicine_id]`" :value="item.medicine_id">
                <div class="grid grid-cols-2 gap-2 md:grid-cols-6">
                    <div class="relative col-span-2 md:col-span-3" x-on:click.outside="if (openFor === index) openFor = null">
                        <label class="label text-xs" :for="`m_name_${index}`">Medicine name *</label>
                        <input data-medicine-input :id="`m_name_${index}`" type="text" class="input" autocomplete="off" :name="`items[${index}][medicine_name]`" x-model="item.medicine_name" x-on:input="unlink(index); lookup(index, item.medicine_name)" placeholder="Search catalogue or type a custom medicine" required>
                        <div x-show="openFor === index && (suggestions[index] || []).length" x-cloak class="absolute z-30 mt-1 max-h-60 w-full overflow-y-auto rounded-lg border border-slate-200 bg-white shadow-lg">
                            <template x-for="m in (suggestions[index] || [])" :key="m.id">
                                <button type="button" class="block w-full px-3 py-2 text-left hover:bg-slate-50" x-on:click="pick(index, m)"><span class="block text-sm font-medium" x-text="m.label"></span><span class="block text-xs text-slate-500" x-text="[m.generic_name, m.route].filter(Boolean).join(' · ')"></span></button>
                            </template>
                        </div>
                    </div>
                    <div><label class="label text-xs" :for="`m_gen_${index}`">Generic</label><input :id="`m_gen_${index}`" class="input" :name="`items[${index}][generic_name]`" x-model="item.generic_name"></div>
                    <div><label class="label text-xs" :for="`m_str_${index}`">Strength</label><input :id="`m_str_${index}`" class="input" :name="`items[${index}][strength]`" x-model="item.strength" placeholder="e.g. 500 mg"></div>
                    <div><label class="label text-xs" :for="`m_route_${index}`">Route</label><input :id="`m_route_${index}`" class="input" list="rx-routes" :name="`items[${index}][route]`" x-model="item.route"></div>
                    <div><label class="label text-xs" :for="`m_dose_${index}`">Dosage *</label><input :id="`m_dose_${index}`" class="input" :name="`items[${index}][dosage]`" x-model="item.dosage" placeholder="e.g. 1 tablet" required></div>
                    <div class="col-span-2 md:col-span-1"><label class="label text-xs" :for="`m_freq_${index}`">Frequency *</label><input :id="`m_freq_${index}`" class="input" list="rx-frequencies" :name="`items[${index}][frequency]`" x-model="item.frequency" required></div>
                    <div><label class="label text-xs" :for="`m_time_${index}`">Timing</label><input :id="`m_time_${index}`" class="input" list="rx-timings" :name="`items[${index}][timing]`" x-model="item.timing"></div>
                    <div><label class="label text-xs" :for="`m_days_${index}`">Duration (days) *</label><input :id="`m_days_${index}`" type="number" min="1" max="365" class="input" :name="`items[${index}][duration_days]`" x-model="item.duration_days" required></div>
                    <div><label class="label text-xs" :for="`m_qty_${index}`">Total quantity</label><input :id="`m_qty_${index}`" class="input" :name="`items[${index}][quantity]`" x-model="item.quantity"></div>
                    <div class="col-span-2 md:col-span-6"><label class="label text-xs" :for="`m_ins_${index}`">Special instructions</label><input :id="`m_ins_${index}`" class="input" :name="`items[${index}][instructions]`" x-model="item.instructions" maxlength="500"></div>
                </div>
            </div>
        </template>
    </div>

    <div class="mt-3 flex flex-wrap items-center gap-2">
        <button type="button" class="btn btn-secondary btn-sm" x-on:click="add()"><x-icon name="plus" /> Add medicine</button>
        <template x-if="templates.length">
            <div class="flex items-center gap-2">
                <label for="rx_tpl" class="text-xs text-slate-500">or apply template</label>
                <select id="rx_tpl" class="input h-8 w-auto py-1 text-xs" x-on:change="applyTemplate($event.target.value); $event.target.value = ''">
                    <option value="">Choose…</option>
                    <template x-for="t in templates" :key="t.id"><option :value="t.id" x-text="t.name"></option></template>
                </select>
            </div>
        </template>
        <p class="ml-auto text-xs text-slate-500" x-show="items.length"><span x-text="items.length"></span> medicine(s) · longest course <span x-text="totalDays()"></span> day(s)</p>
    </div>
    <p x-show="!items.length" class="mt-2 text-xs text-slate-500">No medicines added.</p>
</div>
