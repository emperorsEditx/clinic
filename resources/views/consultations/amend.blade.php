<x-layouts.app :title="'Amend '.$c->consultation_number">
    <x-page-header :title="'Amend consultation '.$c->consultation_number" :subtitle="$c->patient->full_name.' · completed '.fmt_datetime($c->completed_at)" :back="route('consultations.show', $c)" />

    <div class="mb-4 flex items-start gap-2 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-[13px] text-amber-900"><x-icon name="info" class="mt-0.5 size-4 shrink-0" /><p>Amendments are recorded alongside the original. Every changed field keeps its previous value in the amendment history, together with your reason. Prescriptions are amended separately from the prescription page.</p></div>

    <form method="POST" action="{{ route('consultations.amend.store', $c) }}" class="max-w-4xl space-y-6">
        @csrf
        <div class="card"><div class="card-body space-y-4">
            <x-field name="symptoms" label="Symptoms" type="textarea" rows="3" :value="$c->symptoms" />
            <x-field name="clinical_observations" label="Clinical observations" type="textarea" rows="3" :value="$c->clinical_observations" />
            <x-field name="diagnosis" label="Diagnosis" type="textarea" rows="2" required :value="$c->diagnosis" />
            <x-field name="treatment_plan" label="Treatment plan" type="textarea" rows="3" :value="$c->treatment_plan" />
            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <x-field name="follow_up_date" label="Follow-up date" type="date" :value="$c->follow_up_date" />
                <x-field class="md:col-span-2" name="follow_up_instructions" label="Follow-up instructions" type="textarea" rows="2" :value="$c->follow_up_instructions" />
            </div>
            <x-field name="doctor_notes" label="Doctor's notes" type="textarea" rows="2" :value="$c->doctor_notes" />
        </div></div>
        <div class="card"><div class="card-body">
            <x-field name="reason" label="Reason for amendment" required placeholder="e.g. Correcting transcription error in diagnosis" help="Minimum 5 characters. Stored permanently in the audit trail." />
        </div></div>
        <div class="flex justify-end gap-2"><a href="{{ route('consultations.show', $c) }}" class="btn btn-secondary">Cancel</a><button class="btn btn-primary">Record amendment</button></div>
    </form>
</x-layouts.app>
