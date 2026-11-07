<x-field class="sm:col-span-2" name="name" label="Test name" required :value="$t?->name" :id="'tn_'.$sfx" />
<x-field name="category" label="Category" required :value="$t?->category" list="lab-categories" :id="'tc_'.$sfx" />
<x-field name="code" label="Code" :value="$t?->code" :id="'tcd_'.$sfx" />
<x-field class="sm:col-span-2" name="sample_type" label="Sample type" :value="$t?->sample_type" placeholder="e.g. Blood, Urine" :id="'ts_'.$sfx" />
