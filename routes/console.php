<?php

use App\Domain\Hotel\Services\ProviderPriceRefreshScheduler;
use App\Models\ProviderRequest;
use App\Models\RoomCalendar;
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

// The shared SSP table says which hotels are due. Every minute those hotel due
// cycles are synchronized into local hotel/provider state. Each registered provider
// then claims work independently using its own quota. The shared hotel due time is
// advanced only after all mapped provider states for that cycle are terminal.
Schedule::call(static fn (): int => app(ProviderPriceRefreshScheduler::class)->dispatch())
    ->name('hotel-provider-price-refresh')
    ->everyMinute()
    ->withoutOverlapping();

// Remove expired room-calendar days and provider request logs past their retention deadline.
Schedule::command('model:prune', ['--model' => [RoomCalendar::class, ProviderRequest::class]])
    ->everyFiveMinutes()
    ->withoutOverlapping();

// Manual provider sync commands remain available independently of scheduler_enabled.
