<?php

use Illuminate\Support\Facades\Schedule;

// Hotel catalog only: no city sync, per-property HTTP requests, rooms, or availability.
Schedule::command('grs:sync-hotels')
    ->weeklyOn(5, '05:00')
    ->timezone('Asia/Tehran')
    ->withoutOverlapping();

// Legacy automatic GRS availability and generic room sync are paused until
// their independent flows are validated. Their commands/jobs remain unchanged.
