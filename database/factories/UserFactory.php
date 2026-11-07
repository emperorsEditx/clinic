<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/** @extends Factory<User> */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'phone' => fake()->numerify('03#########'),
            'is_active' => true,
            'remember_token' => Str::random(10),
        ];
    }

    public function role(string $role): static
    {
        return $this->afterCreating(fn (User $u) => $u->assignRole($role));
    }

    public function admin(): static
    {
        return $this->role(User::ROLE_ADMIN)->state(['designation' => 'Administrator']);
    }

    public function doctor(): static
    {
        return $this->role(User::ROLE_DOCTOR)->state(fn () => [
            'designation' => 'Consultant',
            'specialization' => fake()->randomElement(['General Medicine', 'Family Medicine', 'Paediatrics', 'Internal Medicine']),
            'qualifications' => 'MBBS',
            'registration_number' => 'REG-'.fake()->unique()->numerify('#####'),
        ]);
    }

    public function receptionist(): static
    {
        return $this->role(User::ROLE_RECEPTIONIST)->state(['designation' => 'Receptionist']);
    }

    public function labTechnician(): static
    {
        return $this->role(User::ROLE_LAB)->state(['designation' => 'Lab Technician']);
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
