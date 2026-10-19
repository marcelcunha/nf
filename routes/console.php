<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

foreach (config('sefaz.schedule_times') as $time) {
    Schedule::command('nfe:import')
        ->timezone(config('sefaz.schedule_timezone'))
        ->dailyAt($time)
        ->withoutOverlapping();
}
