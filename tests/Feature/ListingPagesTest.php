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
        $this->get('/nfe-imports/1')->assertRedirect('/login');
        $this->get('/nfe-imports/1/danfe')->assertRedirect('/login');
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

    public function test_nfe_details_page_lists_items_from_the_xml(): void
    {
        $import = NfeImport::factory()->create(['xml' => file_get_contents(base_path('tests/Fixtures/nfe.xml'))]);

        $this->actingAs(User::factory()->create())
            ->get("/nfe-imports/{$import->id}")
            ->assertInertia(fn (Assert $page) => $page
                ->component('nfe-imports/show')
                ->has('details.items', 2)
                ->where('details.items.0.name', 'Parafuso')
                ->where('details.items.1.code', 'ABC2')
                ->where('details.totals.total', '21.50')
                ->where('details.items.0.taxes.icms', '0')
                ->where('details.taxes.icms', '0')
                ->where('details.taxes.approximate', null));
    }

    public function test_danfe_is_streamed_inline_as_pdf(): void
    {
        $import = NfeImport::factory()->create(['xml' => file_get_contents(base_path('tests/Fixtures/nfe-danfe.xml'))]);

        $response = $this->actingAs(User::factory()->create())->get("/nfe-imports/{$import->id}/danfe");

        $response->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringContainsString('inline', $response->headers->get('Content-Disposition'));
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_danfe_fails_gracefully_when_xml_is_invalid(): void
    {
        $import = NfeImport::factory()->create(['xml' => '<NFe/>']);

        $this->actingAs(User::factory()->create())
            ->get("/nfe-imports/{$import->id}/danfe")
            ->assertStatus(422);
    }

    public function test_nfe_details_reads_taxes_per_item(): void
    {
        $import = NfeImport::factory()->create(['xml' => file_get_contents(base_path('tests/Fixtures/nfe-danfe.xml'))]);

        $this->actingAs(User::factory()->create())
            ->get("/nfe-imports/{$import->id}")
            ->assertInertia(fn (Assert $page) => $page
                ->where('details.items.0.taxes.icms', '2.70')
                ->where('details.items.0.taxes.ipi', '0')
                ->where('details.taxes.icms', '2.70'));
    }
}
