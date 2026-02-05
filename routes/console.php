<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule commands
Schedule::command('app:send-activity-application-mail')->dailyAt('06:00');

// Work the queue every minute, but only if there are jobs in the queue
// The queue is used for sending emails
Schedule::command('queue:work --stop-when-empty')->everyMinute()->withoutOverlapping();