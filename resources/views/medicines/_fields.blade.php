<x-field class="sm:col-span-2" name="name" label="Brand / medicine name" required :value="$m?->name" :id="'mn_'.$sfx" />
<x-field name="generic_name" label="Generic name" :value="$m?->generic_name" :id="'mg_'.$sfx" />
<x-field name="form" label="Form" type="select" :options="collect(config('clinic.medicine_forms'))->mapWithKeys(fn ($f) => [$f => $f])" :value="$m?->form" :id="'mf_'.$sfx" />
<x-field name="strength" label="Strength" :value="$m?->strength" placeholder="e.g. 500 mg" :id="'ms_'.$sfx" />
<x-field name="default_route" label="Default route" type="select" :options="collect(config('clinic.routes'))->mapWithKeys(fn ($f) => [$f => $f])" :value="$m?->default_route" :id="'mr_'.$sfx" />
<x-field class="sm:col-span-2" name="manufacturer" label="Manufacturer" :value="$m?->manufacturer" :id="'mm_'.$sfx" />
