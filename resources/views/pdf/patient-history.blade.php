<!DOCTYPE html>
<html><head><meta charset="utf-8"><title>{{ $patient->patient_code }} — Medical history</title>@include('pdf._styles')</head>
<body>
    <div class="footer"><table style="width:100%"><tr><td>Confidential medical record · {{ $branding['clinic_name'] }}</td><td class="right">{{ $patient->patient_code }} · generated {{ fmt_datetime(now()) }}</td></tr></table></div>

    @include('pdf._header', ['docTitle' => 'Patient Medical History', 'docSubtitle' => $patient->patient_code])

    <div class="section">
        <p class="section-title">Patient information</p>
        <table class="kv" style="width: 100%">
            <tr><td class="k">Name</td><td><strong>{{ $patient->full_name }}</strong></td><td class="k">Patient ID</td><td>{{ $patient->patient_code }}</td></tr>
            <tr><td class="k">Gender / Age</td><td>{{ $patient->gender?->label() }} / {{ $patient->currentAge() ?? '—' }}</td><td class="k">Date of birth</td><td>{{ fmt_date($patient->date_of_birth) }}</td></tr>
            <tr><td class="k">Phone</td><td>{{ $patient->phone ?? '—' }}</td><td class="k">Blood group</td><td>{{ $patient->blood_group ?? '—' }}</td></tr>
            <tr><td class="k">Address</td><td colspan="3">{{ $patient->address ?? '—' }}</td></tr>
            <tr><td class="k">Registered</td><td>{{ fmt_date($patient->registration_date) }}</td><td class="k">Emergency contact</td><td>{{ $patient->emergency_contact_name ? $patient->emergency_contact_name.' '.$patient->emergency_contact_phone : '—' }}</td></tr>
        </table>
    </div>

    @php $allergies = $patient->allergies->where('status', \App\Enums\RecordStatus::Active); @endphp
    <div class="section">
        <p class="section-title">Allergies</p>
        @if ($allergies->isEmpty())<p class="muted">No known allergies recorded.</p>
        @else<div class="alert">@foreach ($allergies as $a)<strong>{{ $a->allergen }}</strong> ({{ $a->allergy_type->label() }}, {{ strtolower($a->severity->label()) }}{{ $a->reaction ? ' – '.$a->reaction : '' }})@if(! $loop->last); @endif @endforeach</div>@endif
    </div>

    <div class="section">
        <p class="section-title">Medical history</p>
        @if ($patient->conditions->isEmpty())<p class="muted">Nothing recorded.</p>@else
        <table class="grid"><thead><tr><th>Type</th><th>Entry</th><th>Status</th><th>Dates</th><th>Notes</th></tr></thead><tbody>
            @foreach ($patient->conditions->sortBy(fn ($c) => $c->category->value) as $c)
                <tr><td>{{ $c->category->label() }}</td><td>{{ $c->name }}{{ $c->relation ? ' ('.$c->relation.')' : '' }}</td><td>{{ $c->status->label() }}</td><td>{{ fmt_date($c->onset_date, '') }}{{ $c->resolved_date ? ' – '.fmt_date($c->resolved_date) : '' }}</td><td>{{ $c->notes }}</td></tr>
            @endforeach
        </tbody></table>@endif
    </div>

    <div class="section">
        <p class="section-title">Medications</p>
        <table class="grid"><thead><tr><th>Medicine</th><th>Source</th><th>Period</th><th>Status</th></tr></thead><tbody>
            @foreach ($patient->medicationCourses as $m)<tr><td>{{ $m->medicine_name }}</td><td>Clinic prescribed</td><td>{{ fmt_date($m->start_date) }} – {{ fmt_date($m->expected_end_date) }} ({{ $m->duration_days }}d)</td><td>{{ $m->status->label() }}</td></tr>@endforeach
            @foreach ($patient->otherMedications as $m)<tr><td>{{ $m->medicine_name }} {{ $m->dosage }} {{ $m->frequency }}</td><td>Other: {{ $m->prescribing_source ?? 'not specified' }}</td><td>{{ fmt_date($m->start_date) }} – {{ fmt_date($m->end_date, 'ongoing') }}</td><td>{{ $m->status->label() }}</td></tr>@endforeach
            @if ($patient->medicationCourses->isEmpty() && $patient->otherMedications->isEmpty())<tr><td colspan="4" class="muted">None recorded.</td></tr>@endif
        </tbody></table>
    </div>

    @if ($patient->medicalHistories->isNotEmpty())
        <div class="section">
            <p class="section-title">Clinical notes</p>
            @foreach ($patient->medicalHistories as $n)<p style="margin-bottom: 5px"><strong>{{ $n->title }}</strong> <span class="small muted">{{ fmt_date($n->created_at) }} · {{ $n->recorder?->name }}</span><br><span class="pre">{{ $n->notes }}</span></p>@endforeach
        </div>
    @endif

    <div class="section">
        <p class="section-title">Visits ({{ $patient->visits->count() }})</p>
        <table class="grid"><thead><tr><th>Date</th><th>Visit</th><th>Type</th><th>Doctor</th><th>Complaint</th><th>Status</th></tr></thead><tbody>
            @forelse ($patient->visits as $v)<tr><td>{{ fmt_datetime($v->visit_at) }}</td><td>{{ $v->visit_number }}</td><td>{{ $v->visitTypeLabel() }}</td><td>{{ $v->doctor->name }}</td><td>{{ $v->chief_complaint }}</td><td>{{ $v->status->label() }}</td></tr>@empty<tr><td colspan="6" class="muted">No visits.</td></tr>@endforelse
        </tbody></table>
    </div>

    <div class="section">
        <p class="section-title">Consultation summaries</p>
        @forelse ($patient->consultations as $c)
            <div style="border-bottom: 1px solid #e2e8f0; padding: 5px 0; page-break-inside: avoid">
                <p><strong>{{ fmt_datetime($c->completed_at) }}</strong> · {{ $c->consultation_number }} · {{ $c->doctor->name }}@if($c->amendment_count) <span class="badge">amended</span>@endif</p>
                @if ($c->symptoms)<p><span class="muted">Symptoms:</span> {{ $c->symptoms }}</p>@endif
                @if ($c->vitals && ! $c->vitals->isEmpty())<p><span class="muted">Vitals:</span> {{ collect([$c->vitals->bloodPressure() ? 'BP '.$c->vitals->bloodPressure() : null, $c->vitals->pulse_rate ? 'Pulse '.$c->vitals->pulse_rate : null, $c->vitals->temperature ? 'Temp '.$c->vitals->temperature.'°C' : null, $c->vitals->oxygen_saturation ? 'SpO2 '.$c->vitals->oxygen_saturation.'%' : null, $c->vitals->weight ? 'Wt '.(float) $c->vitals->weight.'kg' : null])->filter()->implode(', ') }}</p>@endif
                <p><span class="muted">Diagnosis:</span> {{ $c->diagnosis }}</p>
                @if ($c->treatment_plan)<p><span class="muted">Plan:</span> {{ $c->treatment_plan }}</p>@endif
            </div>
        @empty
            <p class="muted">No completed consultations.</p>
        @endforelse
    </div>

    <div class="section">
        <p class="section-title">Prescription history</p>
        @forelse ($patient->prescriptions as $rx)
            <div style="border-bottom: 1px solid #e2e8f0; padding: 5px 0; page-break-inside: avoid">
                <p><strong>{{ fmt_date($rx->prescription_date) }}</strong> · {{ $rx->prescription_number }}{{ $rx->version > 1 ? ' v'.$rx->version : '' }} · {{ $rx->doctor->name }} <span class="badge">{{ $rx->status->label() }}</span>{!! $rx->repeat_of_id ? ' <span class="badge">repeat</span>' : '' !!}</p>
                @foreach ($rx->items as $i)<p class="small">• {{ $i->medicine_name }} {{ $i->strength }} — {{ $i->dosage }}, {{ $i->frequency }}{{ $i->timing ? ', '.$i->timing : '' }}, {{ $i->duration_days }} days</p>@endforeach
            </div>
        @empty
            <p class="muted">No prescriptions.</p>
        @endforelse
    </div>

    <div class="section">
        <p class="section-title">Laboratory reports</p>
        <table class="grid"><thead><tr><th>Report date</th><th>Reference</th><th>Test</th><th>Laboratory</th><th>Status</th><th>Summary</th></tr></thead><tbody>
            @forelse ($patient->laboratoryReports as $r)<tr><td>{{ fmt_date($r->report_date) }}</td><td>{{ $r->report_number }}</td><td>{{ $r->test_name }}</td><td>{{ $r->laboratory_name }}</td><td>{{ $r->status->label() }}</td><td>{{ \Illuminate\Support\Str::limit($r->result_summary, 120) }}</td></tr>@empty<tr><td colspan="6" class="muted">No reports.</td></tr>@endforelse
        </tbody></table>
        <p class="small muted" style="margin-top: 3px">Report files are held in the clinic system and are not embedded in this document.</p>
    </div>
</body></html>
