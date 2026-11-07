<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Production-safe seeding: roles/permissions, reference catalogues and the first administrator.
 * Demo data lives in DemoSeeder and is never run automatically.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([RolesAndPermissionsSeeder::class, LaboratoryTestSeeder::class]);

        $email = env('ADMIN_EMAIL');
        $password = env('ADMIN_PASSWORD');
        if ($email && $password && ! User::withTrashed()->where('email', $email)->exists()) {
            $admin = User::query()->create([
                'name' => env('ADMIN_NAME', 'Clinic Administrator'),
                'email' => $email,
                'password' => $password,
                'is_active' => true,
            ]);
            $admin->assignRole(User::ROLE_ADMIN);
            $this->command?->info("Administrator {$email} created.");
        }
    }
}
