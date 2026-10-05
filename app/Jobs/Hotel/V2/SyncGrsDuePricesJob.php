<?php

namespace App\Jobs\Hotel\V2;

use App\Domain\Hotel\Services\GrsPriceRefreshScheduleService;
use App\Domain\Hotel\V2\GrsRefreshSettings;
use App\Domain\Hotel\V2\RateLimitedGrsAdapter;
use App\Services\Alerts\TelegramAlertService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
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

    public function handle(
        GrsPriceRefreshScheduleService $schedules,
        TelegramAlertService $alerts,
    ): void {
        $provider = $schedules->grsProvider();
        if ($provider === null) {
            throw new RuntimeException('GRS provider is not configured.');
        }
        if (!$provider->is_active) {
            return;
        }

        $days = $this->days ?? GrsRefreshSettings::from($provider)['default_days'];
        if ($days < 1 || $days > 3650) {
            throw new RuntimeException('GRS availability days must be between 1 and 3650.');
        }

        if (RateLimitedGrsAdapter::cooldownSeconds() > 0) {
            return;
        }

        $schedules->assertReady();

        // SSP.gds_id identifies GDS accommodations.id. Only schedules with an
        // existing, enabled provider map are returned by due(). Re-check here
        // defensively in case the map changes between selection and dispatch.
        foreach ($schedules->due($provider) as $schedule) {
            $gdsId = (int) $schedule->gds_id;
            $map = $gdsId > 0
                ? $schedules->mapForAccommodation($gdsId, (int) $provider->id)
                : null;

            if ($gdsId <= 0) {
                $alerts->custom(
                    'شناسه نامعتبر هتل در صف نرخ و ظرفیت',
                    'Schedule فعال GRS شناسه هتل داخلی معتبر ندارد.',
                    [
                        'Schedule ID' => (int) $schedule->id,
                        'GDS ID' => $gdsId,
                        'تأمین‌کننده' => (string) $provider->code,
                    ],
                    level: 'warning',
                    tags: ['DomesticHotel', 'GRS'],
                );

                $schedules->mappingIssueHandled((int) $schedule->id, $gdsId);
                continue;
            }

            $grsId = trim((string) ($map?->provider_property_id ?? ''));

            // Missing/blank/disabled provider maps are outside the price-refresh
            // workflow. Do not repair them here and never dispatch availability.
            if ($map === null || $map->is_disabled === true || $grsId === '') {
                continue;
            }

            RefreshGrsPropertyPricesJob::dispatch(
                (int) $schedule->id,
                $gdsId,
                (int) $provider->id,
                $days
            );
        }
    }
}
