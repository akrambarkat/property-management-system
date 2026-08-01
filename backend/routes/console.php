<?php

use App\Console\Commands\DispatchSmsQueue;
use App\Console\Commands\RunSmsScheduler;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Dispatch queued SMS every minute
Schedule::command(DispatchSmsQueue::class)->everyMinute()->withoutOverlapping();

// Run automatic SMS rules every 30 minutes
Schedule::command(RunSmsScheduler::class)->everyThirtyMinutes()->withoutOverlapping();
