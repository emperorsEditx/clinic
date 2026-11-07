<?php

namespace App\Policies;

use App\Models\Patient;
use App\Models\Prescription;
use App\Models\User;

class PrescriptionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('clinical.view');
    }

    public function view(User $user, Prescription $rx): bool
    {
        return $user->can('clinical.view');
    }

    public function update(User $user, Prescription $rx): bool
    {
        return $user->can('prescriptions.manage') && $rx->isDraft() && $rx->doctor_id === $user->id;
    }

    public function amend(User $user, Prescription $rx): bool
    {
        return $user->can('prescriptions.manage') && $user->isDoctor() && $rx->isFinalized();
    }

    public function repeat(User $user, ?Patient $patient = null): bool
    {
        return $user->can('prescriptions.repeat') && $user->isDoctor();
    }

    public function delete(User $user, Prescription $rx): bool
    {
        return $this->update($user, $rx);
    }
}
