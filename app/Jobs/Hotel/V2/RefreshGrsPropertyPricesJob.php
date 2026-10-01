<?php

namespace App\Jobs\Hotel\V2;

use App\Domain\Hotel\Services\GrsPriceRefreshScheduleService;
use App\Domain\Hotel\Services\HotelSyncService;
use App\Domain\Hotel\V2\GrsApiQuotaExceeded;
use App\Domain\Hotel\V2\RateLimitedGrsAdapter;
use App\Services\Alerts\TelegramAlertService;
use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\RequestException;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use RuntimeException;
use Throwable;

/** GRS-specific price/inventory worker; IDs and persistence go through services/repositories. */
class RefreshGrsPropertyPricesJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 100; // Quota/rate-limit releases count as attempts; actionable HTTP failures are handled explicitly.
    public int $maxExceptions = 1;
    public int $timeout = 55; // Below Horizon's 60s worker timeout and Redis retry_after=90.
    public int $uniqueFor = 14400;

    public function __construct(
        public int $scheduleId,
        public int $gdsId,
        public int $providerId,
        public int $days,
    ) {
        $this->onQueue('grs-prices');
    }

    public function uniqueId(): string
    {
        return 'grs-property-price:'.$this->gdsId;
    }

    public function handle(
        HotelSyncService $service,
        GrsPriceRefreshScheduleService $schedules,
        ?TelegramAlertService $alerts = null,
    ): void {
        $alerts ??= app(TelegramAlertService::class);
        $provider = null;
        $grsId = '';
        $hotelName = 'هتل #' . $this->gdsId;
        $from = null;
        $to = null;

        try {
            $provider = $schedules->providerById($this->providerId);
            if ($provider === null || $provider->code !== 'grs' || !$provider->is_active || !$provider->is_online) {
                throw new RuntimeException('GRS provider inactive, offline, missing, or mismatched.');
            }

            // The shared schedule and this job carry our local accommodations.id.
            if ($schedules->active($this->scheduleId, $this->gdsId) === null) {
                return;
            }

            $map = $schedules->mapForAccommodation($this->gdsId, (int) $provider->id);
            $grsId = trim((string) ($map?->provider_property_id ?? ''));
            $accommodation = $schedules->accommodationById($this->gdsId);
            $hotelName = trim((string) ($accommodation?->fa_name ?? ''))
                ?: trim((string) ($accommodation?->en_name ?? ''))
                ?: $hotelName;
            if ($map?->is_disabled === true) {
                return;
            }

            if ($grsId === '') {
                throw new RuntimeException('GRS provider property ID missing from local accommodation map.');
            }

            $from = CarbonImmutable::today('Asia/Tehran');
            $to = $from->addDays($this->days);
            $started = now()->subSeconds(2); // Account for second-granularity DB timestamps.
            $adapter = new RateLimitedGrsAdapter($provider);
            $adapter->withRequestLogContext(
                null,
                self::class,
                'handle',
            );
            $adapter->trackAvailability(
                fn (): mixed => $schedules->requestStarted($this->scheduleId, $this->gdsId),
                fn (): mixed => $schedules->http200($this->scheduleId, $this->gdsId)
            );

            // Each actual HTTP call consumes the provider's existing quota.
            // Persist provider-returned dates even when outside the requested window.
            $mappingsReady = $service->crawlAvailabilityForProperty(
                $provider,
                $adapter,
                $grsId,
                $from,
                $to
            );

            if ($adapter->supplementalError !== null) {
                throw $adapter->supplementalError;
            }

            if (!$mappingsReady) {
                $currentMap = $schedules->mapForAccommodation(
                    $this->gdsId,
                    (int) $provider->id
                );

                if (
                    $currentMap !== null
                    && trim((string) $currentMap->provider_property_id) !== ''
                ) {
                    SyncGrsHotelDetailsJob::dispatch(
                        (int) $provider->id,
                        (int) $currentMap->id
                    )->onQueue('grs-details');
                } else {
                    RepairMissingGrsAccommodationMapsJob::dispatch(
                        (int) $provider->id
                    )->onQueue('grs-hotels');
                }

                // Mapping repair belongs to the catalog/details flows. Do not mark
                // this price refresh successful and do not fail it as an app error.
                return;
            }

            // Empty/partial provider data is a normal provider outcome. Persist whatever
            // valid rows were returned and advance the normal schedule without logging.
            $schedules->verifiedRowCount(
                (int) $provider->id,
                (int) $map->id,
                $this->gdsId,
                $grsId,
                $adapter->lastAvailability,
                $started
            );
            $schedules->persisted($this->scheduleId, $this->gdsId);
        } catch (GrsApiQuotaExceeded $e) {
            // Quota is already exhausted; do not advance the SSP due time.
            $this->release(max(1, $e->retryAfterSeconds));
        } catch (RequestException $e) {
            $status = $e->response?->status();

            if ($status === 429) {
                $retryAfter = max(1, RateLimitedGrsAdapter::cooldownSeconds());

                $alerts->providerRateLimited(
                    (string) ($provider?->code ?? 'grs'),
                    'بروزرسانی نرخ و ظرفیت GRS',
                    $retryAfter,
                    $this->providerReason($e),
                    [
                        'هتل' => $hotelName,
                        'شناسه هتل' => $this->gdsId,
                        'شناسه هتل تأمین‌کننده' => $grsId,
                    ],
                );

                // Provider throttling is temporary. Keep the schedule due and retry
                // this same job after the shared GRS cooldown instead of failing it.
                $this->release(max(1, $retryAfter));

                return;
            }

            if (
                $status === 404
                && $provider !== null
                && $grsId !== ''
                && $from instanceof CarbonImmutable
                && $to instanceof CarbonImmutable
            ) {
                $requestedDays = max(0, $from->diffInDays($to));
                $missingDates = $this->summarizeDateRanges(
                    $this->expectedDates($from, $to)
                );

                $alerts->providerAvailabilityIssue(
                    (string) $provider->code,
                    $hotelName,
                    $this->gdsId,
                    $grsId,
                    $from->format('Y-m-d'),
                    $to->format('Y-m-d'),
                    $requestedDays,
                    0,
                    'not_found',
                    404,
                    $missingDates,
                    $this->providerReason($e),
                );

                $schedules->disableMapForAccommodation(
                    $this->gdsId,
                    (int) $provider->id
                );

                $schedules->providerAnomalyHandled(
                    $this->scheduleId,
                    $this->gdsId
                );

                return;
            }

            $this->fail($e);
        } catch (Throwable $e) {
            $alerts->internalFailure($e);
            $this->fail($e);
        }
    }

    /** @return array<int, string> */
    private function expectedDates(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $dates = [];

        for ($day = $from->startOfDay(); $day->lt($to); $day = $day->addDay()) {
            $dates[] = $day->format('Y-m-d');
        }

        return $dates;
    }

    /** @param array<int, string> $dates */
    private function summarizeDateRanges(array $dates): ?string
    {
        if ($dates === []) {
            return null;
        }

        sort($dates);
        $ranges = [];
        $start = CarbonImmutable::parse($dates[0]);
        $previous = $start;

        foreach (array_slice($dates, 1) as $date) {
            $current = CarbonImmutable::parse($date);

            if ($previous->addDay()->isSameDay($current)) {
                $previous = $current;
                continue;
            }

            $ranges[] = $this->formatRange($start, $previous);
            $start = $current;
            $previous = $current;
        }

        $ranges[] = $this->formatRange($start, $previous);

        $visible = array_slice($ranges, 0, 12);
        $remaining = count($ranges) - count($visible);

        return implode('، ', $visible)
            . ($remaining > 0 ? "، ... (+{$remaining} بازه)" : '');
    }

    private function formatRange(CarbonImmutable $start, CarbonImmutable $end): string
    {
        if ($start->isSameDay($end)) {
            return $start->format('Y-m-d');
        }

        return $start->format('Y-m-d') . ' تا ' . $end->format('Y-m-d');
    }

    private function providerReason(RequestException $exception): ?string
    {
        $data = $exception->response?->json();
        if (!is_array($data)) {
            return null;
        }

        $errorName = data_get($data, 'errors.0.name');
        $errorMessage = data_get($data, 'errors.0.message');
        $message = trim((string) ($data['message'] ?? ''));

        if (is_scalar($errorMessage) && trim((string) $errorMessage) !== '') {
            $detail = trim((string) $errorMessage);

            if (is_scalar($errorName) && trim((string) $errorName) !== '') {
                return trim((string) $errorName) . ': ' . $detail;
            }

            return $detail;
        }

        return $message !== '' ? $message : null;
    }

}
