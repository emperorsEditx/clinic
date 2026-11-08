<?php

/*
| Defaults for clinic configuration. Values marked "setting" are only the
| initial defaults — administrators override them from Settings (stored in
| the clinic_settings table). Nothing here is a clinical recommendation.
*/

return [
    'settings_defaults' => [
        'clinic_name' => env('CLINIC_NAME', 'Silver Line'),
        'clinic_logo' => null,
        'clinic_address' => null,
        'clinic_phone' => null,
        'clinic_email' => null,
        'clinic_website' => null,
        'clinic_registration_number' => null,
        'doctor_info' => null,
        'prescription_header' => null,
        'prescription_footer' => null,
        'patient_id_prefix' => 'PAT',
        'patient_id_format' => '{PREFIX}-{YEAR}-{SEQ}',
        'patient_id_padding' => '5',
        'appointment_slot_minutes' => '15',
        'appointment_day_start' => '09:00',
        'appointment_day_end' => '17:00',
        'appointment_types' => "New Consultation\nFollow-up\nReview\nProcedure\nEmergency",
        'timezone' => env('APP_TIMEZONE', 'UTC'),
        'date_format' => 'd M Y',
        'time_format' => 'h:i A',
    ],

    'date_formats' => [
        'd M Y' => '07 Nov 2026',
        'd/m/Y' => '07/11/2026',
        'm/d/Y' => '11/07/2026',
        'Y-m-d' => '2026-11-07',
        'd-m-Y' => '07-11-2026',
    ],

    'blood_groups' => ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'],

    // Suggestion lists for prescription entry; doctors can always type free text.
    'routes' => ['Oral', 'Sublingual', 'Topical', 'Inhalation', 'Nasal', 'Ophthalmic', 'Otic', 'Rectal', 'Vaginal', 'Intramuscular', 'Intravenous', 'Subcutaneous', 'Transdermal'],
    'frequencies' => ['Once daily', 'Twice daily', 'Three times daily', 'Four times daily', 'Every 6 hours', 'Every 8 hours', 'Every 12 hours', 'At bedtime', 'As needed', 'Once weekly', 'Stat (single dose)'],
    'timings' => ['Before meals', 'After meals', 'With meals', 'Empty stomach', 'Morning', 'Evening', 'At bedtime', 'Any time'],
    'medicine_forms' => ['Tablet', 'Capsule', 'Syrup', 'Suspension', 'Injection', 'Drops', 'Cream', 'Ointment', 'Gel', 'Inhaler', 'Sachet', 'Suppository', 'Spray', 'Patch'],

    'visit_types' => [
        'walk_in' => 'Walk-in',
        'appointment' => 'Appointment',
        'follow_up' => 'Follow-up',
        'medication_review' => 'Medication review',
        'emergency' => 'Emergency',
    ],

    'lab_uploads' => [
        'disk' => env('LAB_REPORTS_DISK', 'local'),
        'directory' => 'lab-reports',
        'max_kb' => (int) env('LAB_REPORT_MAX_KB', 10240),
        'mimes' => ['pdf', 'jpg', 'jpeg', 'png'],
        'mimetypes' => ['application/pdf', 'image/jpeg', 'image/png'],
    ],

    'per_page' => 20,

    // Enable scheduled database + upload backups (spatie/laravel-backup). Requires mysqldump in the runtime.
    'backup_enabled' => (bool) env('BACKUP_ENABLED', false),
];
