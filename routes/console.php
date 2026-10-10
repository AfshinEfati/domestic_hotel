<?php

use App\Models\ProviderRequest;
use App\Models\RoomCalendar;
use Illuminate\Support\Facades\Schedule;

// Catalog sync remains separate from every provider's price refresh.
Schedule::command('grs:sync-hotels')
    ->weeklyOn(5, '05:00')
    ->timezone('Asia/Tehran')
    ->withoutOverlapping();

// Independent details refresh; price/inventory refresh does not wait for this job.
Schedule::command('grs:sync-details')
    ->weeklyOn(5, '02:00')
    ->timezone('Asia/Tehran')
    ->withoutOverlapping();

// Canonical automatic rate/inventory dispatcher. Existing static provider mappings
// can start immediately. The shared SSP schedule selects due hotels, every mapped
// active provider claims work using its own quota, and SSP advances only after all
// provider states for that hotel cycle are terminal.
//
// Scheduling the Artisan command (instead of an inline closure) keeps automatic,
// diagnostic and manual execution on one visible path in `schedule:list`.
Schedule::command('hotel:provider-refresh-dispatch')
    ->name('hotel-provider-price-refresh')
    ->everyMinute()
    ->onOneServer()
    ->withoutOverlapping(2);

// Remove expired room-calendar days and provider request logs past their retention deadline.
Schedule::command('model:prune', ['--model' => [RoomCalendar::class, ProviderRequest::class]])
    ->everyFiveMinutes()
    ->withoutOverlapping();
