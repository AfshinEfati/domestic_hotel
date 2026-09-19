<?php

namespace App\Jobs\Hotel\V2;

use App\Domain\Hotel\Services\GrsPriceRefreshScheduleService;
use App\Domain\Hotel\V2\GrsRefreshSettings;
use App\Domain\Hotel\V2\RateLimitedGrsAdapter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/** GRS-only due scan. Provider and accommodation lookups belong to repositories. */
class SyncGrsDuePricesJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 60;
    public int $uniqueFor = 600;

    public function __construct(public ?int $days = null)
    {
        $this->onQueue('grs-prices');
    }

    public function uniqueId(): string
    {
        return 'grs-due-prices-dispatch';
    }

    public function handle(GrsPriceRefreshScheduleService $schedules): void
    {
        $provider = $schedules->grsProvider();
        if ($provider === null) {
            throw new RuntimeException('GRS provider is not configured.');
        }
        if (!$provider->is_active || !$provider->is_online) {
            Log::warning('GRS prices skipped: provider inactive or offline');
            return;
        }

        $days = $this->days ?? GrsRefreshSettings::from($provider)['default_days'];
        if ($days < 1 || $days > 3650) {
            throw new RuntimeException('GRS availability days must be between 1 and 3650.');
        }

        if (RateLimitedGrsAdapter::cooldownSeconds() > 0) {
            Log::warning('GRS prices paused by API 429 cooldown');
            return;
        }

        $schedules->assertReady();
        // SSP.gds_id identifies GDS accommodations.id. Dispatch does not alter due time.
        $due = $schedules->due($provider);
        $queued = 0;
        $unmapped = 0;

        foreach ($due as $schedule) {
            $gdsId = (int) $schedule->gds_id;
            $map = $gdsId > 0
                ? $schedules->mapForAccommodation($gdsId, (int) $provider->id)
                : null;
            $grsId = trim((string) ($map?->provider_property_id ?? ''));

            if ($gdsId <= 0 || $grsId === '') {
                $unmapped++;
                Log::warning('GRS due property missing local accommodation map; due time unchanged', [
                    'schedule_id' => $schedule->id,
                    'gds_id' => $gdsId,
                ]);
                continue;
            }

            // Only the GDS ID goes to the worker; it resolves the provider ID anew.
            RefreshGrsPropertyPricesJob::dispatch(
                (int) $schedule->id,
                $gdsId,
                (int) $provider->id,
                $days
            );
            $queued++;
        }

        Log::info('GRS due price scan finished', [
            'days' => $days, 'selected' => $due->count(), 'queued' => $queued, 'unmapped' => $unmapped,
        ]);
    }
}
