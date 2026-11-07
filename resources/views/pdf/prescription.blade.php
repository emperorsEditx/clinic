<!DOCTYPE html>
<html><head><meta charset="utf-8"><title>{{ $rx->prescription_number }}</title>@include('pdf._styles')
<style>.rx-symbol { font-size: 22px; font-weight: bold; color: #1b7a73; font-family: 'DejaVu Serif', serif; }</style>
</head>
<body>
    <div class="footer">
        <table style="width:100%"><tr><td>{{ $branding['prescription_footer'] ?: 'This prescription is valid only with the prescribing doctor’s signature.' }}</td><td class="right">{{ $rx->prescription_number }}{{ $rx->version > 1 ? ' · v'.$rx->version : '' }}</td></tr></table>
    </div>

    @include('pdf._header', ['docTitle' => 'Prescription', 'docSubtitle' => $rx->prescription_number.($rx->version > 1 ? ' (version '.$rx->version.')' : '')])

    @if ($branding['prescription_header'])<p class="small pre" style="margin-bottom: 8px">{{ $branding['prescription_header'] }}</p>@endif

    <table style="width: 100%"><tr>
        <td style="width: 55%; vertical-align: top">
            <p style="font-weight: bold; font-size: 12px">{{ $rx->doctor->displayName() }}</p>
            @php $quals = collect([$rx->doctor->qualifications, $rx->doctor->specialization])->filter()->implode(', '); @endphp
            @if ($quals)<p class="small">{{ $quals }}</p>@elseif ($branding['doctor_info'])<p class="small pre">{{ $branding['doctor_info'] }}</p>@endif
            @if ($rx->doctor->registration_number)<p class="small muted">Reg. No. {{ $rx->doctor->registration_number }}</p>@endif
        </td>
        <td style="width: 45%; vertical-align: top" class="right">
            <p><span class="muted">Date:</span> <strong>{{ fmt_date($rx->prescription_date) }}</strong></p>
            @if ($rx->repeatOf)<p class="small muted">Repeat of {{ $rx->repeatOf->prescription_number }}</p>@endif
            @if ($rx->previousVersion)<p class="small muted">Amends {{ $rx->previousVersion->prescription_number }}</p>@endif
        </td>
    </tr></table>

    <div class="section" style="border: 1px solid #e2e8f0; border-radius: 4px; padding: 6px 8px">
        <table class="kv" style="width: 100%"><tr>
            <td class="k" style="width: 60px">Patient</td><td><strong>{{ $rx->patient->full_name }}</strong></td>
            <td class="k" style="width: 60px">ID</td><td>{{ $rx->patient->patient_code }}</td>
            <td class="k" style="width: 70px">Age / Sex</td><td>{{ $rx->patient->currentAge() ?? '—' }} / {{ $rx->patient->gender?->label() }}</td>
        </tr></table>
        @if ($rx->consultation?->diagnosis)<p class="small" style="margin-top: 3px"><span class="muted">Diagnosis:</span> {{ $rx->consultation->diagnosis }}</p>@endif
    </div>

    <div class="section">
        <p class="rx-symbol">&#8478;</p>
        <table class="grid" style="margin-top: 4px">
            <thead><tr><th style="width: 18px">#</th><th>Medicine</th><th>Dosage</th><th>Frequency</th><th>Duration</th><th>Qty</th></tr></thead>
            <tbody>
            @foreach ($rx->items as $i)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td><strong>{{ $i->medicine_name }}</strong> {{ $i->strength }}
                        @if ($i->generic_name)<br><span class="small muted">{{ $i->generic_name }}</span>@endif
                        @if ($i->route)<br><span class="small muted">Route: {{ $i->route }}</span>@endif
                        @if ($i->instructions)<br><span class="small"><em>{{ $i->instructions }}</em></span>@endif
                    </td>
                    <td>{{ $i->dosage }}</td>
                    <td>{{ $i->frequency }}@if($i->timing)<br><span class="small muted">{{ $i->timing }}</span>@endif</td>
                    <td>{{ $i->duration_days }} day{{ $i->duration_days == 1 ? '' : 's' }}</td>
                    <td>{{ $i->quantity ?? '—' }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    @if ($rx->general_instructions)
        <div class="section"><p class="section-title">Instructions</p><p class="pre">{{ $rx->general_instructions }}</p></div>
    @endif
    @if ($rx->notes)
        <div class="section"><p class="section-title">Notes</p><p class="pre">{{ $rx->notes }}</p></div>
    @endif

    <table style="width: 100%; margin-top: 48px"><tr>
        <td style="width: 60%"></td>
        <td style="width: 40%; text-align: center; border-top: 1px solid #334155; padding-top: 4px">
            <p class="small">{{ $rx->doctor->displayName() }}</p><p class="small muted">Signature &amp; stamp</p>
        </td>
    </tr></table>
</body></html>
