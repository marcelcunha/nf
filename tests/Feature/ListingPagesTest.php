<?php

namespace Tests\Feature;

use App\Jobs\ImportNfesFromSefaz;
use App\Models\NfeImport;
use App\Models\Person;
use App\Models\PersonCategory;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ListingPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/products')->assertRedirect('/login');
        $this->get('/people')->assertRedirect('/login');
        $this->get('/nfe-imports')->assertRedirect('/login');
    }

    public function test_products_can_be_searched(): void
    {
        Product::factory()->create(['name' => 'Parafuso sextavado']);
        Product::factory()->create(['name' => 'Porca']);

        $this->actingAs(User::factory()->create())
            ->get('/products?search=parafuso')
            ->assertInertia(fn (Assert $page) => $page
                ->component('products/index')
                ->has('products.data', 1)
                ->where('products.data.0.name', 'Parafuso sextavado'));
    }

    public function test_people_can_be_filtered_by_category(): void
    {
        $supplier = Person::factory()->create();
        $supplier->categories()->attach(PersonCategory::supplier());
        Person::factory()->create();

        $this->actingAs(User::factory()->create())
            ->get('/people?category=fornecedor')
            ->assertInertia(fn (Assert $page) => $page
                ->component('people/index')
                ->has('people.data', 1)
                ->where('people.data.0.id', $supplier->id));
    }

    public function test_nfe_imports_lists_and_dispatches_import(): void
    {
        Queue::fake();
        NfeImport::factory()->create();
        $user = User::factory()->create();

        $this->actingAs($user)->get('/nfe-imports')
            ->assertInertia(fn (Assert $page) => $page
                ->component('nfe-imports/index')
                ->has('imports.data', 1)
                ->where('scheduleTimes', ['06:30', '12:10', '18:40']));

        $this->actingAs($user)->post('/nfe-imports')->assertRedirect();
        Queue::assertPushed(ImportNfesFromSefaz::class);
    }
}
