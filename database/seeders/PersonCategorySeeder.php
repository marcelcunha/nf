<?php

namespace Database\Seeders;

use App\Models\PersonCategory;
use Illuminate\Database\Seeder;

class PersonCategorySeeder extends Seeder
{
    public function run(): void
    {
        PersonCategory::supplier();
        PersonCategory::firstOrCreate(['slug' => 'cliente'], ['name' => 'Cliente']);
    }
}
