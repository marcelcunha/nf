<?php

namespace Database\Factories;

use App\Models\Person;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'supplier_id' => Person::factory(),
            'code' => fake()->unique()->numerify('P#####'),
            'name' => fake()->words(3, true),
            'unit' => 'UN',
            'last_unit_cost' => fake()->randomFloat(4, 1, 500),
        ];
    }
}
