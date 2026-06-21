<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('searches:notify')->hourly();

Schedule::command('tenders:close-expired')->everyMinute();

Schedule::command('queue:work database --stop-when-empty --tries=1 --timeout=1800 --max-time=1799')
    ->everyThirtyMinutes()
    ->withoutOverlapping()
    ->when(fn () => config('queue.default') !== 'sync');