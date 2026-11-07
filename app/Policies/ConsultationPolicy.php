<?php

namespace App\Policies;

use App\Models\Consultation;
use App\Models\User;

class ConsultationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('clinical.view');
    }

    public function view(User $user, Consultation $c): bool
    {
        return $user->can('clinical.view');
    }

    public function update(User $user, Consultation $c): bool
    {
        return $user->can('consultations.manage') && $c->isDraft() && $c->doctor_id === $user->id;
    }

    public function amend(User $user, Consultation $c): bool
    {
        return $user->can('consultations.manage') && ! $c->isDraft() && $c->doctor_id === $user->id;
    }

    public function delete(User $user, Consultation $c): bool
    {
        return $this->update($user, $c);
    }
}
