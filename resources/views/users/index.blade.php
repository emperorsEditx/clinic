<x-layouts.app title="Users">
    <x-page-header title="Users &amp; staff" subtitle="Accounts are deactivated rather than deleted so their clinical records stay attributed.">
        <x-slot:actions><a href="{{ route('users.create') }}" class="btn btn-primary"><x-icon name="user-plus" /> Add user</a></x-slot:actions>
    </x-page-header>
    <div class="card">
        <form method="GET" class="flex flex-wrap items-end gap-3 border-b border-slate-100 p-4">
            <div class="w-full sm:w-72"><label class="label" for="q">Search</label><input id="q" name="q" value="{{ request('q') }}" class="input" placeholder="Name or email"></div>
            <x-field name="role" label="Role" type="select" :options="App\Models\User::roleLabels()" :value="request('role')" empty="Any" />
            <button class="btn btn-secondary"><x-icon name="funnel" /> Apply</button>
        </form>
        @if ($users->isEmpty())
            <x-empty icon="users" title="No users found" />
        @else
            <div class="overflow-x-auto"><table class="table">
                <thead><tr><th>Name</th><th>Email</th><th>Roles</th><th>Last sign-in</th><th>Status</th><th class="text-right"><span class="sr-only">Actions</span></th></tr></thead>
                <tbody>
                @foreach ($users as $user)
                    <tr>
                        <td><span class="font-medium">{{ $user->name }}</span>@if($user->designation || $user->specialization)<div class="text-xs text-slate-500">{{ collect([$user->designation, $user->specialization])->filter()->implode(' · ') }}</div>@endif</td>
                        <td class="text-slate-600">{{ $user->email }}</td>
                        <td>@foreach ($user->roles as $role)<x-badge color="teal">{{ App\Models\User::roleLabels()[$role->name] ?? $role->name }}</x-badge> @endforeach</td>
                        <td class="whitespace-nowrap text-slate-600">{{ $user->last_login_at ? fmt_datetime($user->last_login_at) : 'Never' }}</td>
                        <td>@if($user->is_active)<x-badge color="green">Active</x-badge>@else<x-badge color="slate">Inactive</x-badge>@endif</td>
                        <td class="whitespace-nowrap text-right">
                            <a href="{{ route('users.edit', $user) }}" class="btn btn-ghost btn-sm">Edit</a>
                            @can('deactivate', $user)
                                <form method="POST" action="{{ route('users.toggle', $user) }}" class="inline" @if($user->is_active) data-confirm="{{ $user->name }} will no longer be able to sign in. Their records remain." data-confirm-title="Deactivate user?" data-confirm-label="Deactivate" data-confirm-danger @endif>@csrf<button class="btn btn-ghost btn-sm {{ $user->is_active ? 'text-red-600' : '' }}">{{ $user->is_active ? 'Deactivate' : 'Activate' }}</button></form>
                            @endcan
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table></div>
            {{ $users->links() }}
        @endif
    </div>
</x-layouts.app>
