<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRequest;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class UserController extends Controller
{
    public function __construct(private AuditLogger $audit) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', User::class);

        return view('users.index', [
            'users' => User::query()->with('roles:id,name')
                ->when($request->input('q'), fn ($q, $t) => $q->where(fn ($w) => $w->where('name', 'like', '%'.$t.'%')->orWhere('email', 'like', '%'.$t.'%')))
                ->when($request->input('role'), fn ($q, $r) => $q->role($r))
                ->orderByDesc('is_active')->orderBy('name')->paginate(config('clinic.per_page'))->withQueryString(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', User::class);

        return view('users.create', ['user' => new User(['is_active' => true])]);
    }

    public function store(UserRequest $request): RedirectResponse
    {
        $user = DB::transaction(function () use ($request) {
            $data = $request->safe()->except(['roles', 'password_confirmation']);
            $user = User::query()->create($data + ['is_active' => $request->boolean('is_active', true)]);
            $user->syncRoles($request->input('roles'));
            $this->audit->log('created', $user, 'Created user '.$user->email, ['roles' => $request->input('roles')]);

            return $user;
        });

        return redirect()->route('users.index')->with('success', "User {$user->name} created.");
    }

    public function edit(User $user): View
    {
        $this->authorize('update', $user);

        return view('users.edit', compact('user'));
    }

    public function update(UserRequest $request, User $user): RedirectResponse
    {
        DB::transaction(function () use ($request, $user) {
            $data = $request->safe()->except(['roles', 'password', 'password_confirmation']);
            if ($request->user()->id === $user->id && ! in_array(User::ROLE_ADMIN, $request->input('roles'), true)) {
                abort(422, 'You cannot remove your own administrator role.');
            }
            $original = $user->getOriginal();
            $oldRoles = $user->getRoleNames()->all();
            $user->fill($data);
            if ($request->user()->id !== $user->id) {
                $user->is_active = $request->boolean('is_active');
            }
            if ($request->filled('password')) {
                $user->password = $request->input('password');
            }
            $user->save();
            $user->syncRoles($request->input('roles'));
            $this->audit->updated($user, $original, 'Updated user '.$user->email);
            if ($oldRoles != $request->input('roles')) {
                $this->audit->log('roles_changed', $user, 'Changed roles', ['roles' => ['old' => $oldRoles, 'new' => $request->input('roles')]]);
            }
            if ($request->filled('password')) {
                $this->audit->log('password_reset', $user, 'Administrator reset password');
            }
        });

        return redirect()->route('users.index')->with('success', 'User updated.');
    }

    public function toggle(Request $request, User $user): RedirectResponse
    {
        $this->authorize('deactivate', $user);
        $user->is_active = ! $user->is_active;
        $user->save();
        $this->audit->log($user->is_active ? 'activated' : 'deactivated', $user, ($user->is_active ? 'Activated ' : 'Deactivated ').$user->email);

        return back()->with('success', $user->is_active ? 'User activated.' : 'User deactivated. Their historical records are retained.');
    }
}
