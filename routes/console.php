<?php

use App\Domain\Hotel\Services\ProviderPriceRefreshScheduler;
use Illuminate\Support\Facades\Schedule;

// Catalog sync remains separate from every provider's price refresh.
Schedule::command('grs:sync-hotels')
    ->weeklyOn(5, '05:00')
    ->timezone('Asia/Tehran')
    ->withoutOverlapping();

// Independent details refresh: ten mapped GRS properties per minute.
// Starts after the catalog dispatch; no price/availability jobs are queued here.
Schedule::command('grs:sync-details')
    ->weeklyOn(5, '02:00')
    ->timezone('Asia/Tehran')
    ->withoutOverlapping();

// One repository read per minute for all registered providers. Each provider
// has its own handler, settings, job, API quota and pricing implementation.
// No database query or provider-specific condition is placed in this file.
Schedule::call(static fn (): int => app(ProviderPriceRefreshScheduler::class)->dispatch())
    ->name('hotel-provider-price-refresh')
    ->everyMinute()
    ->withoutOverlapping();

// Manual grs:sync-prices remains available independently of scheduler_enabled.
