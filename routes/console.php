<?php

use App\Domain\Hotel\V2\GrsRefreshSettings;
use App\Models\Provider;
use Illuminate\Support\Facades\Schedule;

// Hotel catalog only: no city sync, per-property HTTP requests, rooms, or availability.
Schedule::command('grs:sync-hotels')
    ->weeklyOn(5, '05:00')
    ->timezone('Asia/Tehran')
    ->withoutOverlapping();

// Read the provider JSON on every scheduler evaluation so admin changes take
// effect without changing .env, editing PHP or rebuilding the config cache.
Schedule::command('grs:sync-prices')
    ->everyMinute()
    ->withoutOverlapping()
    ->when(static function (): bool {
        $provider = Provider::query()->where('code', 'grs')->first();
        return $provider !== null && GrsRefreshSettings::from($provider)['scheduler_enabled'];
    });

// Legacy automatic GRS availability and generic room sync remain paused.
// Their commands and jobs have not been modified by the V2 implementation.
