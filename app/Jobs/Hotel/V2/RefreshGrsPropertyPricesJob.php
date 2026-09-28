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
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/** GRS-specific price/inventory worker; IDs and persistence go through services/repositories. */
class RefreshGrsPropertyPricesJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 100; // Quota releases count as attempts; HTTP errors are not retried here.
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
                Log::warning('GRS price job skipped: shared schedule disabled/remapped', [
                    'schedule_id' => $this->scheduleId, 'gds_id' => $this->gdsId,
                ]);
                return;
            }

            $map = $schedules->mapForAccommodation($this->gdsId, (int) $provider->id);
            $grsId = trim((string) ($map?->provider_property_id ?? ''));
            $accommodation = $schedules->accommodationById($this->gdsId);
            $hotelName = trim((string) ($accommodation?->fa_name ?? ''))
                ?: trim((string) ($accommodation?->en_name ?? ''))
                ?: $hotelName;
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
            $service->crawlAvailabilityForProperty($provider, $adapter, $grsId, $from, $to);
            if ($adapter->supplementalError !== null) {
                throw $adapter->supplementalError;
            }

            // Empty/partial provider data is not an application failure. Persist what
            // was actually returned, advance the normal schedule, and alert operations.
            $count = $schedules->verifiedRowCount(
                (int) $provider->id,
                $this->gdsId,
                $grsId,
                $adapter->lastAvailability,
                $started
            );
            $minutes = $schedules->persisted($this->scheduleId, $this->gdsId);

            $coverage = $this->coverage($adapter->lastAvailability, $from, $to);
            if ($coverage['received_days'] === 0) {
                $alerts->providerAvailabilityIssue(
                    (string) $provider->code,
                    $hotelName,
                    $this->gdsId,
                    $grsId,
                    $from->format('Y-m-d'),
                    $to->format('Y-m-d'),
                    $coverage['requested_days'],
                    0,
                    'empty',
                    null,
                    $coverage['missing_dates'],
                );
            } elseif ($coverage['received_days'] < $coverage['requested_days']) {
                $alerts->providerAvailabilityIssue(
                    (string) $provider->code,
                    $hotelName,
                    $this->gdsId,
                    $grsId,
                    $from->format('Y-m-d'),
                    $to->format('Y-m-d'),
                    $coverage['requested_days'],
                    $coverage['received_days'],
                    'partial',
                    null,
                    $coverage['missing_dates'],
                );
            }

            Log::info('GRS scheduled availability refresh completed', [
                'schedule_id' => $this->scheduleId,
                'gds_id' => $this->gdsId,
                'grs_id' => $grsId,
                'days' => $this->days,
                'received_days' => $coverage['received_days'],
                'missing_days' => $coverage['requested_days'] - $coverage['received_days'],
                'calendar_rows' => $count,
                'next_in_minutes' => $minutes,
            ]);
        } catch (GrsApiQuotaExceeded $e) {
            // Quota is already exhausted; do not advance the SSP due time.
            $this->release(max(1, $e->retryAfterSeconds + 1));
        } catch (RequestException $e) {
            $status = $e->response?->status();

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

                $minutes = $schedules->providerAnomalyHandled(
                    $this->scheduleId,
                    $this->gdsId
                );

                Log::warning('GRS availability returned HTTP 404; handled as provider data issue', [
                    'schedule_id' => $this->scheduleId,
                    'gds_id' => $this->gdsId,
                    'grs_id' => $grsId,
                    'from' => $from->format('Y-m-d'),
                    'to' => $to->format('Y-m-d'),
                    'next_in_minutes' => $minutes,
                ]);

                return;
            }

            if ($status === 429) {
                Log::error('GRS returned HTTP 429; API cooldown enabled; due time unchanged', [
                    'schedule_id' => $this->scheduleId,
                    'gds_id' => $this->gdsId,
                    'cooldown_seconds' => RateLimitedGrsAdapter::cooldownSeconds(),
                ]);
            }

            $this->recordFailure($e);
        } catch (Throwable $e) {
            $this->recordFailure($e);
        }
    }

    /**
     * @return array{requested_days:int, received_days:int, missing_dates:?string}
     */
    private function coverage(
        ?Collection $availability,
        CarbonImmutable $from,
        CarbonImmutable $to,
    ): array {
        $expected = $this->expectedDates($from, $to);
        $expectedLookup = array_fill_keys($expected, true);

        $received = ($availability ?? collect())
            ->pluck('day')
            ->filter()
            ->map(function ($day): ?string {
                try {
                    return CarbonImmutable::parse((string) $day)->format('Y-m-d');
                } catch (Throwable) {
                    return null;
                }
            })
            ->filter(fn (?string $day): bool =>
                $day !== null && isset($expectedLookup[$day])
            )
            ->unique()
            ->sort()
            ->values()
            ->all();

        $missing = array_values(array_diff($expected, $received));

        return [
            'requested_days' => count($expected),
            'received_days' => count($received),
            'missing_dates' => $this->summarizeDateRanges($missing),
        ];
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

    private function recordFailure(Throwable $e): void
    {
        Log::error('GRS scheduled price refresh failed; shared due time unchanged', [
            'schedule_id' => $this->scheduleId,
            'gds_id' => $this->gdsId,
            'error' => $e->getMessage(),
        ]);
        $this->fail($e);
    }
}
