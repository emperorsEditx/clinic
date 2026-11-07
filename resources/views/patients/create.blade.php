<x-layouts.app title="Register patient">
    <x-page-header title="Register patient" :back="route('patients.index')" />
    <form method="POST" action="{{ route('patients.store') }}" novalidate>
        @csrf
        @include('patients._form')
        <div class="mt-6 flex justify-end gap-2"><a href="{{ route('patients.index') }}" class="btn btn-secondary">Cancel</a><button class="btn btn-primary">Register patient</button></div>
    </form>
</x-layouts.app>
