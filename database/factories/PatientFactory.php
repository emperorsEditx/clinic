<?php

namespace Database\Factories;

use App\Enums\Gender;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Patient> */
class PatientFactory extends Factory
{
    protected $model = Patient::class;

    private static int $seq = 0;

    public function definition(): array
    {
        $gender = fake()->randomElement([Gender::Male, Gender::Female]);
        $dob = fake()->optional(0.6)->dateTimeBetween('-85 years', '-1 year');

        return [
            'patient_code' => 'TST-'.str_pad((string) ++self::$seq, 6, '0', STR_PAD_LEFT).'-'.fake()->unique()->numberBetween(1000, 999999),
            'full_name' => fake()->name($gender === Gender::Male ? 'male' : 'female'),
            'gender' => $gender,
            'date_of_birth' => $dob,
            'age' => $dob ? (int) now()->diffInYears($dob, true) : fake()->numberBetween(1, 85),
            'age_recorded_on' => now(),
            'phone' => fake()->numerify('03#########'),
            'address' => fake()->streetAddress().', '.fake()->city(),
            'registration_date' => fake()->dateTimeBetween('-1 year', 'now'),
            'blood_group' => fake()->optional()->randomElement(config('clinic.blood_groups')),
            'emergency_contact_name' => fake()->optional()->name(),
            'emergency_contact_phone' => fake()->optional()->numerify('03#########'),
        ];
    }
}
