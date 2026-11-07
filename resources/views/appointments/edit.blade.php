<x-layouts.app :title="'Edit '.$appointment->appointment_number">
    <x-page-header :title="'Reschedule '.$appointment->appointment_number" :back="route('appointments.show', $appointment)" />
    <form method="POST" action="{{ route('appointments.update', $appointment) }}" class="max-w-3xl">
        @csrf @method('PUT')
        @include('appointments._form')
        <div class="mt-6 flex justify-end gap-2"><a href="{{ route('appointments.show', $appointment) }}" class="btn btn-secondary">Cancel</a><button class="btn btn-primary">Save changes</button></div>
    </form>
</x-layouts.app>
