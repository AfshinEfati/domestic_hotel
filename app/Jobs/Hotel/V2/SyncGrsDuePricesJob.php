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
        if (!$provider->is_active || !$provider->is_online) {
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

        $repairQueued = false;

        // SSP.gds_id identifies GDS accommodations.id. Dispatch does not alter due time.
        foreach ($schedules->due($provider) as $schedule) {
            $gdsId = (int) $schedule->gds_id;
            $map = $gdsId > 0
                ? $schedules->mapForAccommodation($gdsId, (int) $provider->id)
                : null;

            // Disabled maps are an expected state and must stay silent.
            if ($map?->is_disabled === true) {
                continue;
            }

            $grsId = trim((string) ($map?->provider_property_id ?? ''));

            if ($gdsId <= 0) {
                $alerts->custom(
                    'شناسه نامعتبر هتل در صف نرخ و ظرفیت',
                    'Schedule فعال GRS شناسه هتل داخلی معتبر ندارد و امکان ترمیم خودکار مپ وجود ندارد.',
                    [
                        'Schedule ID' => (int) $schedule->id,
                        'GDS ID' => $gdsId,
                        'تأمین‌کننده' => (string) $provider->code,
                    ],
                    level: 'warning',
                    tags: ['DomesticHotel', 'MapRepair', 'GRS'],
                );

                $schedules->mappingIssueHandled((int) $schedule->id, $gdsId);
                continue;
            }

            if ($map === null || $grsId === '') {
                if (!$repairQueued) {
                    RepairMissingGrsAccommodationMapsJob::dispatch((int) $provider->id)
                        ->onQueue('grs-hotels');
                    $repairQueued = true;
                }

                // Do not advance the schedule yet. The repair job first attempts to
                // rebuild the missing property map from the authoritative GRS catalog.
                continue;
            }

            // Only the GDS ID goes to the worker; it resolves the provider ID anew.
            RefreshGrsPropertyPricesJob::dispatch(
                (int) $schedule->id,
                $gdsId,
                (int) $provider->id,
                $days
            );
        }
    }
}
