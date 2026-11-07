<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('users.manage');
    }

    public function view(User $user, User $model): bool
    {
        return $user->can('users.manage');
    }

    public function create(User $user): bool
    {
        return $user->can('users.manage');
    }

    public function update(User $user, User $model): bool
    {
        return $user->can('users.manage');
    }

    /** Accounts are deactivated, never hard-deleted, so historical records keep their author. */
    public function deactivate(User $user, User $model): bool
    {
        return $user->can('users.manage') && $user->id !== $model->id;
    }
}
