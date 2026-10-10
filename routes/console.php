<?php

use App\Models\ProviderRequest;
use App\Models\RoomCalendar;
use Illuminate\Support\Facades\Schedule;

// Catalog/detail synchronization remains independent from rate refresh. Existing
// provider mappings can therefore start receiving prices immediately; these jobs
// only keep static GRS data fresh in the background.
Schedule::command('grs:sync-hotels')
    ->weeklyOn(5, '05:00')
    ->timezone('Asia/Tehran')
    ->withoutOverlapping();

Schedule::command('grs:sync-details')
    ->weeklyOn(5, '05:15')
    ->timezone('Asia/Tehran')
    ->withoutOverlapping();

// Canonical automatic rate/inventory dispatcher. The shared SSP schedule decides
// which hotels are due. Every mapped active provider gets its own local state and
// independently claims only as much work as its API quota permits. A hotel cycle
// advances in SSP only after every mapped provider reaches a terminal state.
//
// Use the Artisan command instead of a closure so schedule:list, scheduler output,
// deployment diagnostics and manual fallback all exercise the exact same path.
Schedule::command('hotel:provider-refresh-dispatch')
    ->name('hotel-provider-price-refresh')
    ->everyMinute()
    ->onOneServer()
    ->withoutOverlapping(2);

// Remove expired room-calendar days and provider request logs past their retention deadline.
Schedule::command('model:prune', ['--model' => [RoomCalendar::class, ProviderRequest::class]])
    ->everyFiveMinutes()
    ->onOneServer()
    ->withoutOverlapping(5);
