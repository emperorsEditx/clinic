<x-layouts.app title="Book appointment">
    <x-page-header title="Book appointment" :back="route('appointments.index')" />
    <form method="POST" action="{{ route('appointments.store') }}" class="max-w-3xl">
        @csrf
        @include('appointments._form')
        <div class="mt-6 flex justify-end gap-2"><a href="{{ route('appointments.index') }}" class="btn btn-secondary">Cancel</a><button class="btn btn-primary">Book appointment</button></div>
    </form>
</x-layouts.app>
