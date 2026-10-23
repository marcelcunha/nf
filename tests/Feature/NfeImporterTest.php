<?php

use App\Models\NfeImport;
use App\Models\Person;
use App\Models\Product;
use App\Services\NfeImporter;

function nfeImporterXml(): string
{
    return file_get_contents(base_path('tests/Fixtures/nfe.xml'));
}

test('creates supplier in supplier category and products', function () {
    $import = app(NfeImporter::class)->import(nfeImporterXml());

    $supplier = Person::where('document', '12345678000195')->firstOrFail();
    expect($supplier->name)->toBe('Fornecedor Teste Ltda')
        ->and($supplier->categories->contains('slug', 'fornecedor'))->toBeTrue()
        ->and($supplier->products()->count())->toBe(2)
        ->and(Product::where('code', 'ABC1')->first()->ean)->toBeNull()
        ->and(Product::where('code', 'ABC2')->first()->ean)->toBe('7891234567895')
        ->and($import->access_key)->toBe('35260112345678000195550010000001231000000019')
        ->and($import->items_count)->toBe(2);
});

test('reimporting the same nfe does not duplicate', function () {
    $importer = app(NfeImporter::class);
    $importer->import(nfeImporterXml());

    expect($importer->import(nfeImporterXml()))->toBeNull()
        ->and(NfeImport::count())->toBe(1)
        ->and(Person::count())->toBe(1)
        ->and(Product::count())->toBe(2);
});

test('updates existing product cost from a new nfe', function () {
    $importer = app(NfeImporter::class);
    $importer->import(nfeImporterXml());

    $newer = str_replace(
        ['0000001231000000019', '<nNF>123</nNF>', '<vUnCom>1.5000</vUnCom>'],
        ['0000001241000000019', '<nNF>124</nNF>', '<vUnCom>2.0000</vUnCom>'],
        nfeImporterXml(),
    );
    $importer->import($newer);

    expect(Product::count())->toBe(2)
        ->and(Product::where('code', 'ABC1')->first()->last_unit_cost)->toBe('2.0000');
});
