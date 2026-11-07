<x-layouts.app title="Edit patient">
    <x-page-header :title="'Edit '.$patient->full_name" :subtitle="$patient->patient_code" :back="route('patients.show', $patient)" />
    <form method="POST" action="{{ route('patients.update', $patient) }}" novalidate>
        @csrf @method('PUT')
        @include('patients._form')
        <div class="mt-6 flex justify-end gap-2"><a href="{{ route('patients.show', $patient) }}" class="btn btn-secondary">Cancel</a><button class="btn btn-primary">Save changes</button></div>
    </form>
</x-layouts.app>
