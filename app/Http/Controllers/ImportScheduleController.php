<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateImportScheduleRequest;
use App\Models\SefazSyncState;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ImportScheduleController extends Controller
{
    public function edit(): Response
    {
        return Inertia::render('import-schedule', [
            'times' => SefazSyncState::current()->scheduleTimes(),
            'timezone' => config('sefaz.schedule_timezone'),
        ]);
    }

    public function update(UpdateImportScheduleRequest $request): RedirectResponse
    {
        $times = $request->validated('times');
        sort($times);

        SefazSyncState::current()->update(['schedule_times' => $times]);

        return back()->with('success', 'Horários de importação salvos.');
    }
}
