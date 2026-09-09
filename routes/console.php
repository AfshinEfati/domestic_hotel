<?php

use App\Jobs\Hotel\SyncGrsAvailabilityJob;
use Illuminate\Support\Facades\Schedule;
Schedule::job(new SyncGrsAvailabilityJob())
    ->dailyAt('02:00')
    ->name('sync-grs-availability');


Schedule::command('fetch-hotel-data')->weeklyOn(5, '02:00'); // friday at 2:00 AM
Schedule::command('fetch:grs-hotel')->weeklyOn(5, '03:00');
