<?php

namespace App\Policies;

use App\Enums\LabReportStatus;
use App\Models\LaboratoryReport;
use App\Models\User;

class LaboratoryReportPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('lab.view');
    }

    public function view(User $user, LaboratoryReport $r): bool
    {
        return $user->can('lab.view');
    }

    public function download(User $user, LaboratoryReport $r): bool
    {
        return $user->can('lab.view');
    }

    public function create(User $user): bool
    {
        return $user->can('lab.upload');
    }

    public function review(User $user, LaboratoryReport $r): bool
    {
        return $user->can('lab.review') && $r->status === LabReportStatus::Completed;
    }
}
