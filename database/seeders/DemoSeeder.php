<?php

namespace Database\Seeders;

use App\Models\Medicine;
use App\Models\Patient;
use App\Models\User;
use App\Services\AppointmentService;
use App\Services\ConsultationService;
use App\Services\LaboratoryService;
use App\Services\PatientService;
use App\Services\VisitService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;

/**
 * Demo/staging data only: `php artisan db:seed --class=DemoSeeder`.
 * Every record is created through the real services so workflows and audit logs are exercised.
 * Medicine entries are illustrative catalogue items, not clinical recommendations.
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(DatabaseSeeder::class);

        $admin = $this->user('admin@clinic.test', 'Clinic Admin', 'admin');
        $doctor = $this->user('doctor@clinic.test', 'Dr. Sara Ahmed', 'doctor', ['specialization' => 'Family Medicine', 'qualifications' => 'MBBS, FCPS', 'registration_number' => 'PMDC-12345']);
        $doctor2 = $this->user('doctor2@clinic.test', 'Dr. Bilal Khan', 'doctor', ['specialization' => 'Internal Medicine', 'qualifications' => 'MBBS', 'registration_number' => 'PMDC-67890']);
        $reception = $this->user('reception@clinic.test', 'Ayesha Malik', 'receptionist');
        $this->user('lab@clinic.test', 'Usman Tariq', 'labTechnician');

        foreach ([
            ['Paracetamol', 'Paracetamol', 'Tablet', '500 mg'], ['Amoxicillin', 'Amoxicillin', 'Capsule', '500 mg'],
            ['Omeprazole', 'Omeprazole', 'Capsule', '20 mg'], ['Cetirizine', 'Cetirizine', 'Tablet', '10 mg'],
            ['Metformin', 'Metformin', 'Tablet', '500 mg'], ['Amlodipine', 'Amlodipine', 'Tablet', '5 mg'],
            ['Ibuprofen', 'Ibuprofen', 'Tablet', '400 mg'], ['ORS', 'Oral Rehydration Salts', 'Sachet', null],
            ['Salbutamol', 'Salbutamol', 'Inhaler', '100 mcg'], ['Azithromycin', 'Azithromycin', 'Tablet', '500 mg'],
        ] as [$name, $generic, $form, $strength]) {
            Medicine::query()->firstOrCreate(['name' => $name, 'strength' => $strength, 'form' => $form], ['generic_name' => $generic, 'default_route' => $form === 'Inhaler' ? 'Inhalation' : 'Oral', 'is_active' => true]);
        }

        if (Patient::query()->count() >= 20) {
            return;
        }

        Auth::login($reception);
        $patients = app(PatientService::class);
        $created = collect(range(1, 24))->map(fn () => $patients->register(Patient::factory()->make()->only(['full_name', 'gender', 'age', 'date_of_birth', 'phone', 'address', 'blood_group', 'registration_date']), $reception, true));

        $appointments = app(AppointmentService::class);
        $types = settings()->appointmentTypes();
        foreach ($created->take(8)->values() as $i => $p) {
            $appointments->create(['patient_id' => $p->id, 'doctor_id' => $i % 2 ? $doctor2->id : $doctor->id, 'appointment_date' => today()->addDays($i % 3)->toDateString(), 'appointment_time' => sprintf('%02d:%02d', 9 + intdiv($i, 2), ($i % 2) * 30), 'appointment_type' => $types[$i % count($types)], 'reason' => 'Routine review'], $reception);
        }

        Auth::login($doctor);
        $visits = app(VisitService::class);
        $consultations = app(ConsultationService::class);
        $lab = app(LaboratoryService::class);
        $para = Medicine::query()->where('name', 'Paracetamol')->first();

        foreach ($created->slice(8, 6)->values() as $i => $p) {
            $visit = $visits->create($p, ['doctor_id' => $doctor->id, 'visit_type' => 'walk_in', 'chief_complaint' => 'Fever and body aches'], $reception);
            $c = $consultations->start($visit, $doctor);
            $consultations->save($c, [
                'symptoms' => 'Fever for 2 days, body aches',
                'clinical_observations' => 'Alert, mildly dehydrated',
                'diagnosis' => 'Viral fever (demo)',
                'treatment_plan' => 'Symptomatic care, hydration',
                'follow_up_date' => today()->addDays(5 + $i)->toDateString(),
                'follow_up_instructions' => 'Return if fever persists',
                'vitals' => ['systolic_bp' => 120, 'diastolic_bp' => 80, 'temperature' => 38.4, 'pulse_rate' => 92, 'oxygen_saturation' => 98, 'weight' => 70],
                'items' => [[
                    'medicine_id' => $para?->id, 'medicine_name' => 'Paracetamol', 'strength' => '500 mg', 'dosage' => '1 tablet',
                    'route' => 'Oral', 'frequency' => 'Twice daily', 'timing' => 'After meals', 'duration_days' => 3, 'quantity' => '6 tablets',
                ]],
                'lab_tests' => $i === 0 ? [['test_name' => 'Complete Blood Count (CBC)', 'priority' => 'routine']] : [],
            ], $doctor, complete: true);
        }

        $visits->create($created->get(14), ['doctor_id' => $doctor->id, 'visit_type' => 'walk_in', 'chief_complaint' => 'Cough'], $reception);
        Auth::logout();
    }

    private function user(string $email, string $name, string $state, array $extra = []): User
    {
        return User::query()->where('email', $email)->first()
            ?? User::factory()->{$state}()->create(['email' => $email, 'name' => $name, 'password' => 'password'] + $extra);
    }
}
