<?php

namespace Database\Factories;

use App\Models\NfeImport;
use App\Models\Person;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NfeImport>
 */
class NfeImportFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'access_key' => fake()->unique()->numerify(str_repeat('#', 44)),
            'supplier_id' => Person::factory(),
            'number' => fake()->numberBetween(1, 99999),
            'series' => 1,
            'issued_at' => now(),
            'total' => fake()->randomFloat(2, 10, 5000),
            'xml' => '<NFe/>',
        ];
    }
}
