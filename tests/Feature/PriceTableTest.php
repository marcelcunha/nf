<?php

use App\Models\NfeImport;
use App\Models\Person;
use App\Models\Product;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

function publishedTable(): NfeImport
{
    $supplier = Person::factory()->create(['name' => 'Fornecedor Teste Ltda']);
    $import = NfeImport::factory()->published()->for($supplier, 'supplier')->create([
        'xml' => file_get_contents(base_path('tests/Fixtures/nfe.xml')),
    ]);
    Product::factory()->create(['supplier_id' => $supplier->id, 'code' => 'ABC1', 'name' => 'Parafuso', 'sale_price' => 3.5, 'last_unit_cost' => 1.5]);
    Product::factory()->create(['supplier_id' => $supplier->id, 'code' => 'ABC2', 'name' => 'Porca', 'sale_price' => null]);

    return $import;
}

test('guests are redirected to login', function () {
    $this->get('/tabelas-de-preco')->assertRedirect('/login');
    $this->get('/tabelas-de-preco/1')->assertRedirect('/login');
});

test('employees list only published tables', function () {
    NfeImport::factory()->published()->create();
    NfeImport::factory()->create();

    $this->actingAs(User::factory()->employee()->create())->get('/tabelas-de-preco')
        ->assertInertia(fn (Assert $page) => $page
            ->component('price-tables/index')
            ->has('tables.data', 1));
});

test('employees see only product, supplier and final price', function () {
    $import = publishedTable();

    $this->actingAs(User::factory()->employee()->create())
        ->get("/tabelas-de-preco/{$import->id}")
        ->assertInertia(fn (Assert $page) => $page
            ->component('price-tables/show')
            ->where('table.supplier', 'Fornecedor Teste Ltda')
            ->has('items', 1)
            ->where('items.0.code', 'ABC1')
            ->where('items.0.name', 'Parafuso')
            ->where('items.0.sale_price', '3.5000')
            ->has('items.0', 3)
            ->missing('details')
            ->missing('nfe')
            ->missing('supplier'));
});

test('employees cannot open an unpublished table', function () {
    $import = NfeImport::factory()->create();

    $this->actingAs(User::factory()->employee()->create())
        ->get("/tabelas-de-preco/{$import->id}")
        ->assertNotFound();
});

test('saving prices publishes the table to employees', function () {
    $supplier = Person::factory()->create();
    $product = Product::factory()->create(['supplier_id' => $supplier->id]);
    $import = NfeImport::factory()->for($supplier, 'supplier')->create();

    $this->actingAs(User::factory()->create())
        ->put("/nfe-importadas/{$import->id}/precificar", ['prices' => [['product_id' => $product->id, 'sale_price' => '9.9']]])
        ->assertSessionHasNoErrors();

    expect($import->fresh()->price_table_published_at)->not->toBeNull();
});

test('saving prices again keeps the original publication date', function () {
    $import = NfeImport::factory()->published()->create(['price_table_published_at' => '2026-01-10 10:00:00']);
    $product = Product::factory()->create(['supplier_id' => $import->supplier_id]);

    $this->actingAs(User::factory()->create())
        ->put("/nfe-importadas/{$import->id}/precificar", ['prices' => [['product_id' => $product->id, 'sale_price' => '5']]]);

    expect($import->fresh()->price_table_published_at->toDateString())->toBe('2026-01-10');
});

test('admins can remove a price table from employees', function () {
    $import = NfeImport::factory()->published()->create();

    $this->actingAs(User::factory()->create())->delete("/nfe-importadas/{$import->id}/publicar")->assertRedirect();

    expect($import->fresh()->price_table_published_at)->toBeNull();
});

test('employees cannot save prices or remove a table', function () {
    $import = NfeImport::factory()->published()->create();
    $product = Product::factory()->create(['supplier_id' => $import->supplier_id]);
    $employee = User::factory()->employee()->create();

    $this->actingAs($employee)
        ->put("/nfe-importadas/{$import->id}/precificar", ['prices' => [['product_id' => $product->id, 'sale_price' => '1']]])
        ->assertForbidden();
    $this->actingAs($employee)->delete("/nfe-importadas/{$import->id}/publicar")->assertForbidden();

    expect($import->fresh()->price_table_published_at)->not->toBeNull()
        ->and($product->fresh()->sale_price)->toBeNull();
});
