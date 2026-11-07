<?php

namespace App\Policies;

use App\Models\MedicationCourse;
use App\Models\User;

class MedicationCoursePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('clinical.view');
    }

    public function update(User $user, MedicationCourse $course): bool
    {
        return $user->can('medications.manage');
    }
}
