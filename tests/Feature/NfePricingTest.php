<?php

use App\Models\NfeImport;
use App\Models\Person;
use App\Models\Product;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

function importWithItemTaxes(Person $supplier, string $quantity = '10.0000'): NfeImport
{
    $xml = str_replace(
        ['</ICMS></imposto>', '<qCom>10.0000</qCom>'],
        ['</ICMS><IPI><IPITrib><vIPI>1.50</vIPI></IPITrib></IPI><PIS><PISAliq><vPIS>0.99</vPIS></PISAliq></PIS><ICMSST><vICMSST>0.50</vICMSST></ICMSST></imposto>', "<qCom>{$quantity}</qCom>"],
        file_get_contents(base_path('tests/Fixtures/nfe-danfe.xml')),
    );

    return NfeImport::factory()->for($supplier, 'supplier')->create(['xml' => $xml]);
}

test('guests are redirected to login', function () {
    $this->get('/nfe-imports/1/pricing')->assertRedirect('/login');
    $this->put('/nfe-imports/1/pricing')->assertRedirect('/login');
});

test('unit cost adds ipi, icms st and import tax but not icms, pis or cofins', function () {
    $supplier = Person::factory()->create();
    $product = Product::factory()->create(['supplier_id' => $supplier->id, 'code' => 'ABC1', 'sale_price' => 3]);
    $import = importWithItemTaxes($supplier);

    $this->actingAs(User::factory()->create())
        ->get("/nfe-imports/{$import->id}/pricing")
        ->assertInertia(fn (Assert $page) => $page
            ->component('nfe-imports/pricing')
            ->where('nfe.id', $import->id)
            ->where('details.items.0.unit_cost_with_taxes', '1.7000')
            ->where('details.items.0.product_id', $product->id)
            ->where('details.items.0.sale_price', '3.0000'));
});

test('unit cost is null when quantity is zero', function () {
    $import = importWithItemTaxes(Person::factory()->create(), '0.0000');

    $this->actingAs(User::factory()->create())
        ->get("/nfe-imports/{$import->id}/pricing")
        ->assertInertia(fn (Assert $page) => $page
            ->where('details.items.0.unit_cost_with_taxes', null)
            ->where('details.items.0.product_id', null));
});

test('sale prices are saved for the suppliers products', function () {
    $supplier = Person::factory()->create();
    $product = Product::factory()->create(['supplier_id' => $supplier->id]);
    $import = NfeImport::factory()->for($supplier, 'supplier')->create();

    $this->actingAs(User::factory()->create())
        ->put("/nfe-imports/{$import->id}/pricing", ['prices' => [['product_id' => $product->id, 'sale_price' => '12.5']]])
        ->assertSessionHasNoErrors();

    expect($product->fresh()->sale_price)->toBe('12.5000');
});

test('prices of other suppliers products are ignored', function () {
    $other = Product::factory()->create();
    $import = NfeImport::factory()->create();

    $this->actingAs(User::factory()->create())
        ->put("/nfe-imports/{$import->id}/pricing", ['prices' => [['product_id' => $other->id, 'sale_price' => '9']]]);

    expect($other->fresh()->sale_price)->toBeNull();
});

test('negative sale price is rejected', function () {
    $product = Product::factory()->create();
    $import = NfeImport::factory()->create();

    $this->actingAs(User::factory()->create())
        ->put("/nfe-imports/{$import->id}/pricing", ['prices' => [['product_id' => $product->id, 'sale_price' => '-1']]])
        ->assertSessionHasErrors('prices.0.sale_price');
});
