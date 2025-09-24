<?php

use App\Jobs\Hotel\SyncGrsAvailabilityJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::job(new SyncGrsAvailabilityJob())
    ->dailyAt('02:00')
    ->name('sync-grs-availability');
