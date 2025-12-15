<?php

use App\Jobs\Hotel\SyncGrsAvailabilityJob;
use Illuminate\Support\Facades\Schedule;
Schedule::job(new SyncGrsAvailabilityJob())
    ->dailyAt('02:00')
    ->name('sync-grs-availability');
