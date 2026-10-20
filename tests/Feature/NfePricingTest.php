<?php

namespace Tests\Feature;

use App\Models\NfeImport;
use App\Models\Person;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class NfePricingTest extends TestCase
{
    use RefreshDatabase;

    private function importWithItemTaxes(Person $supplier, string $quantity = '10.0000'): NfeImport
    {
        $xml = str_replace(
            ['</ICMS></imposto>', '<qCom>10.0000</qCom>'],
            ['</ICMS><IPI><IPITrib><vIPI>1.50</vIPI></IPITrib></IPI><PIS><PISAliq><vPIS>0.99</vPIS></PISAliq></PIS><ICMSST><vICMSST>0.50</vICMSST></ICMSST></imposto>', "<qCom>{$quantity}</qCom>"],
            file_get_contents(base_path('tests/Fixtures/nfe-danfe.xml')),
        );

        return NfeImport::factory()->for($supplier, 'supplier')->create(['xml' => $xml]);
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/nfe-imports/1/pricing')->assertRedirect('/login');
        $this->put('/nfe-imports/1/pricing')->assertRedirect('/login');
    }

    public function test_unit_cost_adds_ipi_icms_st_and_import_tax_but_not_icms_pis_cofins(): void
    {
        $supplier = Person::factory()->create();
        $product = Product::factory()->create(['supplier_id' => $supplier->id, 'code' => 'ABC1', 'sale_price' => 3]);
        $import = $this->importWithItemTaxes($supplier);

        $this->actingAs(User::factory()->create())
            ->get("/nfe-imports/{$import->id}/pricing")
            ->assertInertia(fn (Assert $page) => $page
                ->component('nfe-imports/pricing')
                ->where('nfe.id', $import->id)
                ->where('details.items.0.unit_cost_with_taxes', '1.7000')
                ->where('details.items.0.product_id', $product->id)
                ->where('details.items.0.sale_price', '3.0000'));
    }

    public function test_unit_cost_is_null_when_quantity_is_zero(): void
    {
        $import = $this->importWithItemTaxes(Person::factory()->create(), '0.0000');

        $this->actingAs(User::factory()->create())
            ->get("/nfe-imports/{$import->id}/pricing")
            ->assertInertia(fn (Assert $page) => $page
                ->where('details.items.0.unit_cost_with_taxes', null)
                ->where('details.items.0.product_id', null));
    }

    public function test_sale_prices_are_saved_for_the_suppliers_products(): void
    {
        $supplier = Person::factory()->create();
        $product = Product::factory()->create(['supplier_id' => $supplier->id]);
        $import = NfeImport::factory()->for($supplier, 'supplier')->create();

        $this->actingAs(User::factory()->create())
            ->put("/nfe-imports/{$import->id}/pricing", ['prices' => [['product_id' => $product->id, 'sale_price' => '12.5']]])
            ->assertSessionHasNoErrors();

        $this->assertSame('12.5000', $product->fresh()->sale_price);
    }

    public function test_prices_of_other_suppliers_products_are_ignored(): void
    {
        $other = Product::factory()->create();
        $import = NfeImport::factory()->create();

        $this->actingAs(User::factory()->create())
            ->put("/nfe-imports/{$import->id}/pricing", ['prices' => [['product_id' => $other->id, 'sale_price' => '9']]]);

        $this->assertNull($other->fresh()->sale_price);
    }

    public function test_negative_sale_price_is_rejected(): void
    {
        $product = Product::factory()->create();
        $import = NfeImport::factory()->create();

        $this->actingAs(User::factory()->create())
            ->put("/nfe-imports/{$import->id}/pricing", ['prices' => [['product_id' => $product->id, 'sale_price' => '-1']]])
            ->assertSessionHasErrors('prices.0.sale_price');
    }
}
