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
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;
use NFePHP\DA\NFe\Danfe;
use Throwable;

class NfeImportController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'access_key' => ['nullable', 'string', 'max:44'],
            'number' => ['nullable', 'integer', 'min:0'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        return Inertia::render('nfe-imports/index', [
            'filters' => [
                'access_key' => $filters['access_key'] ?? '',
                'number' => $filters['number'] ?? '',
                'from' => $filters['from'] ?? '',
                'to' => $filters['to'] ?? '',
            ],
            'lastRunAt' => SefazSyncState::current()->last_run_at,
            'scheduleTimes' => config('sefaz.schedule_times'),
            'imports' => NfeImport::query()
                ->with('supplier:id,name')
                ->select(['id', 'access_key', 'supplier_id', 'number', 'series', 'issued_at', 'total', 'items_count', 'price_table_published_at'])
                ->when($filters['access_key'] ?? null, fn ($query, $accessKey) => $query->where('access_key', 'like', "%{$accessKey}%"))
                ->when($filters['number'] ?? null, fn ($query, $number) => $query->where('number', 'like', "%{$number}%"))
                ->when($filters['from'] ?? null, fn ($query, $from) => $query->where('issued_at', '>=', Carbon::parse($from)->startOfDay()))
                ->when($filters['to'] ?? null, fn ($query, $to) => $query->where('issued_at', '<=', Carbon::parse($to)->endOfDay()))
                ->latest('issued_at')
                ->paginate(10)
                ->withQueryString(),
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

        $nfeImport->update(['price_table_published_at' => $nfeImport->price_table_published_at ?? now()]);

        return back()->with('success', 'Preços salvos e publicados para os funcionários.');
    }

    public function unpublish(NfeImport $nfeImport): RedirectResponse
    {
        $nfeImport->update(['price_table_published_at' => null]);

        return back()->with('success', 'Tabela de preço removida dos funcionários.');
    }

    /**
     * @return array{nfe: array<string, mixed>, supplier: ?Person, details: array<string, mixed>}
     */
    private function detailsPayload(NfeImport $nfeImport, NfeXmlReader $reader): array
    {
        $nfeImport->load('supplier');

        return [
            'nfe' => $nfeImport->only(['id', 'access_key', 'number', 'series', 'issued_at', 'total', 'price_table_published_at']),
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
