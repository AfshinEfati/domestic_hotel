<?php

use App\Domain\Hotel\Services\GrsPriceRefreshScheduleService;
use Illuminate\Support\Facades\Schedule;

// Weekly catalog sync is independent of provider availability requests.
Schedule::command('grs:sync-hotels')
    ->weeklyOn(5, '05:00')
    ->timezone('Asia/Tehran')
    ->withoutOverlapping();

// This schedule only invokes the provider-specific pricing command. The service
// checks editable GRS settings through its repository; no database query here.
Schedule::command('grs:sync-prices')
    ->everyMinute()
    ->withoutOverlapping()
    ->when(static fn (): bool => app(GrsPriceRefreshScheduleService::class)->schedulerEnabled());

// Legacy automatic GRS availability and generic room sync remain paused.
