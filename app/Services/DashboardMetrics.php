<?php

namespace App\Services;

use App\Models\NfeImport;
use App\Models\Product;
use App\Models\SefazCertificate;
use App\Models\SefazSyncState;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardMetrics
{
    public const PERIODS = ['month', '3m', '12m'];

    private Carbon $start;

    private Carbon $end;

    private Carbon $previousStart;

    private Carbon $previousEnd;

    public function __construct(public readonly string $period = 'month')
    {
        $months = match ($period) {
            '3m' => 3,
            '12m' => 12,
            default => 1,
        };

        $this->end = now();
        $this->start = now()->subMonths($months - 1)->startOfMonth();
        $this->previousStart = $this->start->copy()->subMonths($months);
        $this->previousEnd = $this->start->copy()->subSecond();
    }

    /**
     * @return array{count: array{value: int, change: float|null}, total: array{value: string, change: float|null}, suppliers: array{active: int, new: int}, products: array{without_price: int, total: int}}
     */
    public function kpis(): array
    {
        $current = $this->totalsBetween($this->start, $this->end);
        $previous = $this->totalsBetween($this->previousStart, $this->previousEnd);

        $newSuppliers = NfeImport::query()
            ->select('supplier_id')
            ->groupBy('supplier_id')
            ->havingRaw('min(issued_at) >= ?', [$this->start])
            ->get()
            ->count();

        return [
            'count' => [
                'value' => $current['count'],
                'change' => $this->percentChange($previous['count'], $current['count']),
            ],
            'total' => [
                'value' => number_format($current['total'], 2, '.', ''),
                'change' => $this->percentChange($previous['total'], $current['total']),
            ],
            'suppliers' => [
                'active' => NfeImport::whereBetween('issued_at', [$this->start, $this->end])->distinct('supplier_id')->count('supplier_id'),
                'new' => $newSuppliers,
            ],
            'products' => [
                'without_price' => Product::whereNull('sale_price')->count(),
                'total' => Product::count(),
            ],
        ];
    }

    /**
     * Compras dos últimos 12 meses, incluindo meses sem notas.
     *
     * @return list<array{month: string, label: string, total: string}>
     */
    public function monthlyTotals(): array
    {
        $first = now()->subMonths(11)->startOfMonth();

        $totals = NfeImport::query()
            ->where('issued_at', '>=', $first)
            ->get(['issued_at', 'total'])
            ->groupBy(fn (NfeImport $import) => $import->issued_at->format('Y-m'))
            ->map(fn ($imports) => $imports->sum('total'));

        return collect(range(0, 11))
            ->map(function (int $offset) use ($first, $totals): array {
                $month = $first->copy()->addMonths($offset);

                return [
                    'month' => $month->format('Y-m'),
                    'label' => $month->locale('pt_BR')->translatedFormat('M'),
                    'total' => number_format((float) ($totals[$month->format('Y-m')] ?? 0), 2, '.', ''),
                ];
            })
            ->all();
    }

    /**
     * Cinco maiores fornecedores do período; o restante é agrupado em "Outros".
     *
     * @return list<array{name: string, total: string}>
     */
    public function topSuppliers(): array
    {
        $rows = NfeImport::query()
            ->join('people', 'people.id', '=', 'nfe_imports.supplier_id')
            ->whereBetween('nfe_imports.issued_at', [$this->start, $this->end])
            ->groupBy('people.id', 'people.name')
            ->orderByDesc(DB::raw('sum(nfe_imports.total)'))
            ->get(['people.name', DB::raw('sum(nfe_imports.total) as total')]);

        $top = $rows->take(5)->map(fn ($row) => [
            'name' => $row->name,
            'total' => number_format((float) $row->total, 2, '.', ''),
        ])->values();

        $others = $rows->slice(5);

        if ($others->isNotEmpty()) {
            $top->push([
                'name' => "Outros ({$others->count()})",
                'total' => number_format((float) $others->sum('total'), 2, '.', ''),
            ]);
        }

        return $top->all();
    }

    /**
     * @return list<array{id: int, issued_at: string, number: int, series: int, supplier: string, total: string}>
     */
    public function recentImports(): array
    {
        return NfeImport::query()
            ->with('supplier:id,name')
            ->latest('issued_at')
            ->limit(5)
            ->get(['id', 'supplier_id', 'number', 'series', 'issued_at', 'total'])
            ->map(fn (NfeImport $import) => [
                'id' => $import->id,
                'issued_at' => $import->issued_at->toIso8601String(),
                'number' => $import->number,
                'series' => $import->series,
                'supplier' => $import->supplier->name,
                'total' => $import->total,
            ])
            ->all();
    }

    /**
     * @return array{last_run_at: string|null, next_run_at: string|null, last_nsu: string, max_nsu: string, up_to_date: bool}
     */
    public function sync(): array
    {
        $state = SefazSyncState::current();

        return [
            'last_run_at' => $state->last_run_at?->toIso8601String(),
            'next_run_at' => $this->nextScheduledRun()->toIso8601String(),
            'last_nsu' => $state->last_nsu,
            'max_nsu' => $state->max_nsu,
            'up_to_date' => (int) $state->last_nsu >= (int) $state->max_nsu,
        ];
    }

    /**
     * @return array{valid_to: string, days_left: int, expired: bool}|null
     */
    public function certificate(): ?array
    {
        $certificate = SefazCertificate::current();

        if (! $certificate) {
            return null;
        }

        return [
            'valid_to' => $certificate->valid_to->toIso8601String(),
            'days_left' => $certificate->daysUntilExpiry(),
            'expired' => $certificate->isExpired(),
        ];
    }

    /**
     * @return array{count: int, total: float}
     */
    private function totalsBetween(Carbon $from, Carbon $to): array
    {
        $row = NfeImport::query()
            ->whereBetween('issued_at', [$from, $to])
            ->selectRaw('count(*) as count, coalesce(sum(total), 0) as total')
            ->first();

        return ['count' => (int) $row->count, 'total' => (float) $row->total];
    }

    private function percentChange(float|int $previous, float|int $current): ?float
    {
        if ($previous == 0) {
            return null;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }

    private function nextScheduledRun(): Carbon
    {
        $timezone = config('sefaz.schedule_timezone');
        $now = now($timezone);

        $candidates = collect(SefazSyncState::current()->scheduleTimes())
            ->map(fn (string $time) => Carbon::parse($time, $timezone))
            ->sort()
            ->values();

        return $candidates->first(fn (Carbon $time) => $time->greaterThan($now))
            ?? $candidates->first()->addDay();
    }
}
