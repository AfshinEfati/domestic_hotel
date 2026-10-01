<?php

namespace App\Jobs\Hotel\V2;

use App\Domain\Hotel\Providers\GRSAdapter;
use App\Domain\Hotel\Services\GrsAccommodationMapRepairService;
use App\Domain\Hotel\Services\GrsPriceRefreshScheduleService;
use App\Domain\Hotel\V2\GrsRefreshSettings;
use App\Services\Alerts\TelegramAlertService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\RequestException;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class RepairMissingGrsAccommodationMapsJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 20;
    public int $timeout = 55;
    public int $uniqueFor = 1800;

    public function __construct(public int $providerId)
    {
        $this->onQueue('grs-hotels');
    }

    public function uniqueId(): string
    {
        return 'grs-missing-accommodation-map-repair:'.$this->providerId;
    }

    public function handle(
        GrsPriceRefreshScheduleService $schedules,
        GrsAccommodationMapRepairService $repair,
        TelegramAlertService $alerts,
    ): void {
        $provider = $schedules->providerById($this->providerId);

        if (
            $provider === null
            || $provider->code !== 'grs'
            || !$provider->is_active
            || !$provider->is_online
        ) {
            return;
        }

        $activeSchedules = $schedules->activeForMappingRepair();
        $accommodationIds = $activeSchedules
            ->pluck('gds_id')
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        $missing = $repair->missingAccommodationIds(
            (int) $provider->id,
            $accommodationIds
        );

        if ($missing === []) {
            return;
        }

        $adapter = (new GRSAdapter($provider))->withRequestLogContext(
            null,
            self::class,
            'handle',
        );

        try {
            $catalog = $adapter->fetchAllPropertiesForMapping();
        } catch (RequestException $e) {
            if ($e->response?->status() === 429) {
                $retryAfter = $e->response?->header('Retry-After');
                $configured = GrsRefreshSettings::from($provider)['api_cooldown_minutes'] * 60;
                $seconds = is_numeric($retryAfter)
                    ? max($configured, (int) $retryAfter)
                    : $configured;

                $this->release(max(1, $seconds));
                return;
            }

            throw $e;
        }

        if ($catalog === []) {
            $alerts->custom(
                'ترمیم مپ GRS انجام نشد',
                'Catalog هتل‌های GRS خالی بود؛ هیچ مپی تغییر نکرد و Job بعداً دوباره تلاش می‌کند.',
                [
                    'تأمین‌کننده' => 'grs',
                    'تعداد هتل بدون مپ' => count($missing),
                ],
                level: 'warning',
                tags: ['DomesticHotel', 'MapRepair', 'GRS'],
            );

            $seconds = GrsRefreshSettings::from($provider)['api_cooldown_minutes'] * 60;
            $this->release(max(60, $seconds));
            return;
        }

        $result = $repair->repair($provider, $missing, $catalog);

        foreach ($result['repaired'] as $gdsId => $propertyId) {
            $map = $schedules->mapForAccommodation(
                (int) $gdsId,
                (int) $provider->id
            );

            if (
                $map !== null
                && trim((string) $map->provider_property_id) === trim((string) $propertyId)
            ) {
                SyncGrsHotelDetailsJob::dispatch(
                    (int) $provider->id,
                    (int) $map->id
                )->onQueue('grs-details');
            }
        }

        foreach ($result['unresolved'] as $gdsId => $failure) {
            $scheduleRows = $activeSchedules
                ->filter(fn ($schedule) => (int) $schedule->gds_id === (int) $gdsId);

            $alerts->custom(
                'ترمیم خودکار مپ هتل GRS انجام نشد',
                'هتل در catalog GRS بررسی شد اما مپ قطعی و امن برای آن پیدا نشد.',
                [
                    'GDS ID' => (int) $gdsId,
                    'هتل' => $failure['hotel'],
                    'تأمین‌کننده' => 'grs',
                    'علت' => $failure['reason'],
                ],
                level: 'warning',
                tags: ['DomesticHotel', 'MapRepair', 'GRS'],
            );

            foreach ($scheduleRows as $schedule) {
                $schedules->mappingIssueHandled(
                    (int) $schedule->id,
                    (int) $gdsId
                );
            }
        }

        // Repaired schedules stay due. The dedicated details job owns hotel,
        // room and rate-plan synchronization; the next price scan only persists
        // availability after those mappings are ready.
    }
}
