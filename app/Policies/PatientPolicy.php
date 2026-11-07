<?php

namespace App\Policies;

use App\Models\Patient;
use App\Models\User;

class PatientPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('patients.view');
    }

    public function view(User $user, Patient $patient): bool
    {
        return $user->can('patients.view');
    }

    public function viewClinical(User $user, Patient $patient): bool
    {
        return $user->can('clinical.view');
    }

    public function create(User $user): bool
    {
        return $user->can('patients.create');
    }

    public function update(User $user, Patient $patient): bool
    {
        return $user->can('patients.update') && ! $patient->trashed();
    }

    public function delete(User $user, Patient $patient): bool
    {
        return $user->can('patients.delete');
    }

    public function restore(User $user, Patient $patient): bool
    {
        return $user->can('patients.delete');
    }

    public function manageHistory(User $user, Patient $patient): bool
    {
        return $user->can('medical_history.manage') && ! $patient->trashed();
    }
}
