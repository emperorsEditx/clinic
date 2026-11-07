<x-layouts.app title="My profile">
    <x-page-header title="My profile" />
    <div class="grid max-w-5xl grid-cols-1 gap-6 lg:grid-cols-2">
        <form method="POST" action="{{ route('profile.update') }}" class="card">
            @csrf @method('PUT')
            <div class="card-header"><h2 class="card-title">Personal details</h2></div>
            <div class="card-body space-y-4">
                <x-field name="name" label="Name" required :value="$user->name" />
                <x-field name="email" label="Email" type="email" required :value="$user->email" />
                <x-field name="phone" label="Phone" :value="$user->phone" />
                <p class="text-xs text-slate-500">Roles: {{ $user->getRoleNames()->map(fn ($r) => App\Models\User::roleLabels()[$r] ?? $r)->implode(', ') }}. Professional details are managed by an administrator.</p>
                <div class="flex justify-end"><button class="btn btn-primary">Save</button></div>
            </div>
        </form>
        <form method="POST" action="{{ route('profile.password') }}" class="card">
            @csrf @method('PUT')
            <div class="card-header"><h2 class="card-title">Change password</h2></div>
            <div class="card-body space-y-4">
                <x-field name="current_password" label="Current password" type="password" required autocomplete="current-password" />
                <x-field name="password" label="New password" type="password" required autocomplete="new-password" help="At least 8 characters with letters and numbers." />
                <x-field name="password_confirmation" label="Confirm new password" type="password" required autocomplete="new-password" />
                <div class="flex justify-end"><button class="btn btn-primary">Change password</button></div>
            </div>
        </form>
    </div>
</x-layouts.app>
