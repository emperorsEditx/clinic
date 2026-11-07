@php
    $parts = array_filter([
        $v->bloodPressure() ? 'BP '.$v->bloodPressure() : null,
        $v->pulse_rate ? 'Pulse '.$v->pulse_rate.' bpm' : null,
        $v->temperature ? 'Temp '.$v->temperature.' °C' : null,
        $v->respiratory_rate ? 'RR '.$v->respiratory_rate.'/min' : null,
        $v->oxygen_saturation ? 'SpO₂ '.$v->oxygen_saturation.'%' : null,
        $v->weight ? 'Wt '.(float) $v->weight.' kg' : null,
        $v->height ? 'Ht '.(float) $v->height.' cm' : null,
        $v->bmi() ? 'BMI '.$v->bmi() : null,
    ]);
@endphp
<span class="text-[13px] text-slate-700">{{ $parts ? implode(' · ', $parts) : '—' }}</span>
