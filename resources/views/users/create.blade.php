<x-layouts.app title="Add user">
    <x-page-header title="Add user" :back="route('users.index')" />
    <form method="POST" action="{{ route('users.store') }}" class="max-w-4xl">
        @csrf
        @include('users._form')
        <div class="mt-6 flex justify-end gap-2"><a href="{{ route('users.index') }}" class="btn btn-secondary">Cancel</a><button class="btn btn-primary">Create user</button></div>
    </form>
</x-layouts.app>
