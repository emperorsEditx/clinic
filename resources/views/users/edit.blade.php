<x-layouts.app :title="'Edit '.$user->name">
    <x-page-header :title="'Edit '.$user->name" :back="route('users.index')" />
    <form method="POST" action="{{ route('users.update', $user) }}" class="max-w-4xl">
        @csrf @method('PUT')
        @include('users._form')
        <div class="mt-6 flex justify-end gap-2"><a href="{{ route('users.index') }}" class="btn btn-secondary">Cancel</a><button class="btn btn-primary">Save changes</button></div>
    </form>
</x-layouts.app>
