<?php

namespace App\Http\Controllers;

use App\Models\NfeImport;
use App\Models\Product;
use App\Services\NfeXmlReader;
use Inertia\Inertia;
use Inertia\Response;

class PriceTableController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('price-tables/index', [
            'tables' => NfeImport::query()
                ->with('supplier:id,name')
                ->published()
                ->select(['id', 'supplier_id', 'number', 'series', 'issued_at', 'price_table_published_at'])
                ->latest('price_table_published_at')
                ->paginate(10),
        ]);
    }

    /**
     * Mostra apenas produto, fornecedor e valor final. Custos, impostos e dados da NF-e nunca são enviados.
     */
    public function show(NfeImport $nfeImport, NfeXmlReader $reader): Response
    {
        abort_if($nfeImport->price_table_published_at === null, 404);

        $nfeImport->load('supplier:id,name');

        $items = $reader->read($nfeImport->xml)['items'];

        $products = Product::query()
            ->where('supplier_id', $nfeImport->supplier_id)
            ->whereIn('code', array_column($items, 'code'))
            ->whereNotNull('sale_price')
            ->get(['code', 'name', 'sale_price'])
            ->keyBy('code');

        return Inertia::render('price-tables/show', [
            'table' => [
                'id' => $nfeImport->id,
                'number' => $nfeImport->number,
                'series' => $nfeImport->series,
                'supplier' => $nfeImport->supplier->name,
                'published_at' => $nfeImport->price_table_published_at->toIso8601String(),
            ],
            'items' => collect($items)
                ->filter(fn (array $item) => $products->has($item['code']))
                ->map(fn (array $item) => [
                    'code' => $item['code'],
                    'name' => $item['name'],
                    'sale_price' => $products[$item['code']]->sale_price,
                ])
                ->values(),
        ]);
    }
}
