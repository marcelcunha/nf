<?php

namespace App\Http\Controllers;

use App\Jobs\ImportNfesFromSefaz;
use App\Models\NfeImport;
use App\Models\SefazSyncState;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class NfeImportController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('nfe-imports/index', [
            'lastRunAt' => SefazSyncState::current()->last_run_at,
            'scheduleTimes' => config('sefaz.schedule_times'),
            'imports' => NfeImport::query()
                ->with('supplier:id,name')
                ->select(['id', 'access_key', 'supplier_id', 'number', 'series', 'issued_at', 'total'])
                ->latest('issued_at')
                ->paginate(20),
        ]);
    }

    public function store(): RedirectResponse
    {
        ImportNfesFromSefaz::dispatch();

        return back()->with('success', 'Importação enviada para a fila.');
    }
}
