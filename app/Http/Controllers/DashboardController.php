<?php

namespace App\Http\Controllers;

use App\Services\DashboardMetrics;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response|RedirectResponse
    {
        if (! $request->user()->isAdmin()) {
            return to_route('price-tables.index');
        }

        $period = $request->validate([
            'period' => ['nullable', Rule::in(DashboardMetrics::PERIODS)],
        ])['period'] ?? 'month';

        $metrics = new DashboardMetrics($period);

        return Inertia::render('dashboard', [
            'period' => $period,
            'kpis' => $metrics->kpis(),
            'months' => $metrics->monthlyTotals(),
            'suppliers' => $metrics->topSuppliers(),
            'recent' => $metrics->recentImports(),
            'sync' => $metrics->sync(),
            'certificate' => $metrics->certificate(),
        ]);
    }
}
