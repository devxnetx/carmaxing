<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('searches:notify')->hourly();

Schedule::command('tenders:close-expired')->everyMinute();

Schedule::command('queue:work --stop-when-empty --max-time=55')
    ->everyMinute()
    ->withoutOverlapping()
    ->when(fn () => config('queue.default') !== 'sync');