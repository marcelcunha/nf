<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateNfePricingRequest;
use App\Jobs\ImportNfesFromSefaz;
use App\Models\NfeImport;
use App\Models\Person;
use App\Models\Product;
use App\Models\SefazSyncState;
use App\Services\NfeXmlReader;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response as HttpResponse;
use Inertia\Inertia;
use Inertia\Response;
use NFePHP\DA\NFe\Danfe;
use Throwable;

class NfeImportController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('nfe-imports/index', [
            'lastRunAt' => SefazSyncState::current()->last_run_at,
            'scheduleTimes' => config('sefaz.schedule_times'),
            'imports' => NfeImport::query()
                ->with('supplier:id,name')
                ->select(['id', 'access_key', 'supplier_id', 'number', 'series', 'issued_at', 'total', 'items_count'])
                ->latest('issued_at')
                ->paginate(20),
        ]);
    }

    public function store(): RedirectResponse
    {
        ImportNfesFromSefaz::dispatch();

        return back()->with('success', 'Importação enviada para a fila.');
    }

    public function show(NfeImport $nfeImport, NfeXmlReader $reader): Response
    {
        return Inertia::render('nfe-imports/show', $this->detailsPayload($nfeImport, $reader));
    }

    public function pricing(NfeImport $nfeImport, NfeXmlReader $reader): Response
    {
        $payload = $this->detailsPayload($nfeImport, $reader);

        $products = Product::query()
            ->where('supplier_id', $nfeImport->supplier_id)
            ->whereIn('code', array_column($payload['details']['items'], 'code'))
            ->get(['id', 'code', 'sale_price'])
            ->keyBy('code');

        $payload['details']['items'] = array_map(fn (array $item): array => $item + [
            'product_id' => $products[$item['code']]->id ?? null,
            'sale_price' => $products[$item['code']]->sale_price ?? null,
        ], $payload['details']['items']);

        return Inertia::render('nfe-imports/pricing', $payload);
    }

    public function updatePricing(UpdateNfePricingRequest $request, NfeImport $nfeImport): RedirectResponse
    {
        foreach ($request->validated('prices') as $price) {
            Product::query()
                ->where('supplier_id', $nfeImport->supplier_id)
                ->whereKey($price['product_id'])
                ->update(['sale_price' => $price['sale_price']]);
        }

        return back()->with('success', 'Preços salvos.');
    }

    /**
     * @return array{nfe: array<string, mixed>, supplier: ?Person, details: array<string, mixed>}
     */
    private function detailsPayload(NfeImport $nfeImport, NfeXmlReader $reader): array
    {
        $nfeImport->load('supplier');

        return [
            'nfe' => $nfeImport->only(['id', 'access_key', 'number', 'series', 'issued_at', 'total']),
            'supplier' => $nfeImport->supplier,
            'details' => $reader->read($nfeImport->xml),
        ];
    }

    public function danfe(NfeImport $nfeImport): HttpResponse
    {
        try {
            $pdf = (new Danfe($nfeImport->xml))->render();
        } catch (Throwable $exception) {
            report($exception);

            abort(422, 'Não foi possível gerar o DANFE desta NF-e.');
        }

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="danfe-'.$nfeImport->access_key.'.pdf"',
        ]);
    }
}
