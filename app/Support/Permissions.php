<?php

namespace App\Support;

use App\Models\User;

/**
 * Single source of truth for permissions and the default role matrix.
 * Administrators can hold several roles (e.g. a clinic owner who is also a doctor).
 */
final class Permissions
{
    public const ALL = [
        'patients.view' => 'View patient demographics',
        'patients.create' => 'Register patients',
        'patients.update' => 'Edit patient demographics',
        'patients.delete' => 'Archive patients',
        'clinical.view' => 'View clinical records (history, consultations, prescriptions, labs)',
        'medical_history.manage' => 'Record and update medical history',
        'appointments.view' => 'View appointments',
        'appointments.manage' => 'Create and update appointments',
        'visits.view' => 'View visits',
        'visits.manage' => 'Record and check in visits',
        'consultations.manage' => 'Create and amend consultations',
        'prescriptions.manage' => 'Create and amend prescriptions',
        'prescriptions.repeat' => 'Authorise repeat prescriptions',
        'medications.manage' => 'Manage medication courses and other medications',
        'medicines.manage' => 'Manage medicine catalogue',
        'lab.view' => 'View laboratory requests and reports',
        'lab.request' => 'Request laboratory tests',
        'lab.process' => 'Update laboratory request status',
        'lab.upload' => 'Upload laboratory reports',
        'lab.review' => 'Review laboratory reports',
        'lab_tests.manage' => 'Manage laboratory test catalogue',
        'reports.view' => 'View reports and analytics',
        'users.manage' => 'Manage users and staff',
        'settings.manage' => 'Manage clinic settings',
        'audit.view' => 'View audit log',
    ];

    public static function matrix(): array
    {
        return [
            User::ROLE_ADMIN => [
                'patients.view', 'patients.create', 'patients.update', 'patients.delete', 'clinical.view',
                'appointments.view', 'appointments.manage', 'visits.view', 'visits.manage',
                'medicines.manage', 'lab.view', 'lab_tests.manage', 'reports.view',
                'users.manage', 'settings.manage', 'audit.view',
            ],
            User::ROLE_DOCTOR => [
                'patients.view', 'clinical.view', 'medical_history.manage',
                'appointments.view', 'visits.view', 'visits.manage',
                'consultations.manage', 'prescriptions.manage', 'prescriptions.repeat', 'medications.manage',
                'medicines.manage', 'lab.view', 'lab.request', 'lab.review',
            ],
            User::ROLE_RECEPTIONIST => [
                'patients.view', 'patients.create', 'patients.update',
                'appointments.view', 'appointments.manage', 'visits.view', 'visits.manage',
            ],
            User::ROLE_LAB => [
                'lab.view', 'lab.process', 'lab.upload', 'lab_tests.manage',
            ],
        ];
    }
}
