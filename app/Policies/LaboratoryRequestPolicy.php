<?php

namespace App\Policies;

use App\Models\LaboratoryRequest;
use App\Models\User;

class LaboratoryRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('lab.view');
    }

    public function view(User $user, LaboratoryRequest $r): bool
    {
        return $user->can('lab.view');
    }

    public function create(User $user): bool
    {
        return $user->can('lab.request') && $user->isDoctor();
    }

    public function updateStatus(User $user, LaboratoryRequest $r): bool
    {
        return $user->can('lab.process');
    }

    public function upload(User $user, LaboratoryRequest $r): bool
    {
        return $user->can('lab.upload');
    }
}
