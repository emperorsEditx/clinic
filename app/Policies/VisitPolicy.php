<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Visit;

class VisitPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('visits.view');
    }

    public function view(User $user, Visit $visit): bool
    {
        return $user->can('visits.view');
    }

    public function create(User $user): bool
    {
        return $user->can('visits.manage');
    }

    public function cancel(User $user, Visit $visit): bool
    {
        return $user->can('visits.manage');
    }

    public function consult(User $user, Visit $visit): bool
    {
        return $user->can('consultations.manage') && $user->isDoctor();
    }
}
