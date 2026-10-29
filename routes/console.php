<?php

use App\Models\SefazSyncState;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('nfe:import')
    ->everyMinute()
    ->when(fn (): bool => in_array(
        now(config('sefaz.schedule_timezone'))->format('H:i'),
        SefazSyncState::current()->scheduleTimes(),
        true,
    ))
    ->withoutOverlapping();
