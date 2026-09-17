<?php

use Illuminate\Support\Facades\Schedule;

// Hotel catalog only: no city sync, per-property HTTP requests, rooms, or availability.
Schedule::command('grs:sync-hotels')
    ->weeklyOn(5, '05:00')
    ->timezone('Asia/Tehran')
    ->withoutOverlapping();

// GRS pricing is independently scheduled. Shared SSP next_gds_run_at decides
// what is due; this minute tick only dispatches due jobs, not all hotels.
// Disabled until the shared columns and database connection are validated.
Schedule::command('grs:sync-prices')
    ->everyMinute()
    ->withoutOverlapping()
    ->when(fn (): bool => (bool) config('grs.availability.scheduler_enabled', false));

// Legacy automatic GRS availability and generic room sync remain paused.
// Their commands and jobs have not been modified by the V2 implementation.
