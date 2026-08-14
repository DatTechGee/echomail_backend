<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('campaigns:dispatch')->everyMinute()->withoutOverlapping();

// Process queued campaign emails every minute. This lets a single
// "schedule:run" cron entry drive both dispatch and delivery.
Schedule::command('queue:work database --stop-when-empty --max-time=55 --tries=3')
    ->everyMinute()
    ->withoutOverlapping();

Schedule::command('summary:send-weekly')->weekly()->mondays()->at('09:00');
