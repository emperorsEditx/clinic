<x-layouts.app title="Record visit">
    <x-page-header title="Record visit" subtitle="Register a walk-in or unscheduled encounter. Appointments are checked in from the appointment list." :back="route('visits.index')" />
    <form method="POST" action="{{ route('visits.store') }}" class="max-w-3xl">
        @csrf
        <div class="card"><div class="card-body grid grid-cols-1 gap-5 md:grid-cols-2">
            <div class="md:col-span-2"><x-patient-picker :patient="$patient" /></div>
            <x-field name="doctor_id" label="Doctor" type="select" required :options="$doctors->mapWithKeys(fn ($d) => [$d->id => $d->name.($d->specialization ? ' — '.$d->specialization : '')])" :value="$defaultDoctor" />
            <x-field name="visit_type" label="Visit type" type="select" required :options="config('clinic.visit_types')" value="walk_in" :empty="false" />
            <x-field name="visit_at" label="Arrival time" type="datetime-local" :value="now()" help="Leave as now for patients arriving at the desk." />
            <x-field name="chief_complaint" label="Chief complaint" placeholder="Reason for coming in today" />
            <x-field class="md:col-span-2" name="notes" label="Front-desk notes" type="textarea" rows="2" />
        </div></div>
        <div class="mt-6 flex justify-end gap-2"><a href="{{ route('visits.index') }}" class="btn btn-secondary">Cancel</a><button class="btn btn-primary">Record visit</button></div>
    </form>
</x-layouts.app>
