<?php

namespace Tests\Feature;

use App\Models\NfeImport;
use App\Models\Person;
use App\Models\Product;
use App\Services\NfeImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NfeImporterTest extends TestCase
{
    use RefreshDatabase;

    private function xml(): string
    {
        return file_get_contents(base_path('tests/Fixtures/nfe.xml'));
    }

    public function test_creates_supplier_in_supplier_category_and_products(): void
    {
        $import = app(NfeImporter::class)->import($this->xml());

        $supplier = Person::where('document', '12345678000195')->firstOrFail();
        $this->assertSame('Fornecedor Teste Ltda', $supplier->name);
        $this->assertTrue($supplier->categories->contains('slug', 'fornecedor'));
        $this->assertSame(2, $supplier->products()->count());
        $this->assertNull(Product::where('code', 'ABC1')->first()->ean);
        $this->assertSame('7891234567895', Product::where('code', 'ABC2')->first()->ean);
        $this->assertSame('35260112345678000195550010000001231000000019', $import->access_key);
    }

    public function test_reimporting_same_nfe_does_not_duplicate(): void
    {
        $importer = app(NfeImporter::class);
        $importer->import($this->xml());

        $this->assertNull($importer->import($this->xml()));
        $this->assertSame(1, NfeImport::count());
        $this->assertSame(1, Person::count());
        $this->assertSame(2, Product::count());
    }

    public function test_updates_existing_product_cost_from_new_nfe(): void
    {
        $importer = app(NfeImporter::class);
        $importer->import($this->xml());

        $newer = str_replace(
            ['0000001231000000019', '<nNF>123</nNF>', '<vUnCom>1.5000</vUnCom>'],
            ['0000001241000000019', '<nNF>124</nNF>', '<vUnCom>2.0000</vUnCom>'],
            $this->xml(),
        );
        $importer->import($newer);

        $this->assertSame(2, Product::count());
        $this->assertSame('2.0000', Product::where('code', 'ABC1')->first()->last_unit_cost);
    }
}
