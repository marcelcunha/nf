<?php

namespace Database\Factories;

use App\Models\PersonCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PersonCategory>
 */
class PersonCategoryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->word();

        return ['name' => ucfirst($name), 'slug' => $name];
    }
}
