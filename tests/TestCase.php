<?php

namespace Tests;

use App\Models\Patient;
use App\Models\User;
use Database\Seeders\LaboratoryTestSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function seedRoles(): void
    {
        $this->seed([RolesAndPermissionsSeeder::class, LaboratoryTestSeeder::class]);
    }

    protected function admin(): User
    {
        return User::factory()->admin()->create();
    }

    protected function doctor(): User
    {
        return User::factory()->doctor()->create();
    }

    protected function receptionist(): User
    {
        return User::factory()->receptionist()->create();
    }

    protected function labTech(): User
    {
        return User::factory()->labTechnician()->create();
    }

    protected function patient(array $attrs = []): Patient
    {
        return Patient::factory()->create($attrs);
    }

    /** A valid prescription item payload. Values are test fixtures, not clinical guidance. */
    protected function item(array $overrides = []): array
    {
        return array_merge(['medicine_name' => 'Test Medicine A', 'strength' => '10 mg', 'dosage' => '1 tablet', 'route' => 'Oral',
            'frequency' => 'Twice daily', 'timing' => 'After meals', 'duration_days' => 5, 'quantity' => '10'], $overrides);
    }
}
