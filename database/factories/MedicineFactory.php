<?php

namespace Database\Factories;

use App\Models\Medicine;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Medicine> */
class MedicineFactory extends Factory
{
    protected $model = Medicine::class;

    public function definition(): array
    {
        return [
            'name' => ucfirst(fake()->unique()->lexify('??????')).'ex',
            'generic_name' => fake()->lexify('??????ine'),
            'form' => fake()->randomElement(['Tablet', 'Capsule', 'Syrup']),
            'strength' => fake()->randomElement(['5 mg', '10 mg', '250 mg', '500 mg']),
            'default_route' => 'Oral',
            'is_active' => true,
        ];
    }
}
