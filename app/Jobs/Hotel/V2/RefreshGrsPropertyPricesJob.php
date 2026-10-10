<?php

namespace App\Jobs\Hotel\V2;

use App\Domain\Hotel\Exceptions\InvalidProviderAvailabilityDataException;
use App\Domain\Hotel\Services\GrsPriceRefreshScheduleService;
use App\Domain\Hotel\Services\HotelSyncService;
use App\Domain\Hotel\Services\ProviderRefreshCoordinator;
use App\Domain\Hotel\Support\ProviderRefreshOutcome;
use App\Domain\Hotel\V2\GrsApiQuotaExceeded;
use App\Domain\Hotel\V2\RateLimitedGrsAdapter;
use App\Services\Alerts\ProviderDiagnosticAlertService;
use App\Services\Alerts\TelegramAlertService;
use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use RuntimeException;
use Throwable;

/** GRS-specific price/inventory worker; IDs and persistence go through services/repositories. */
class RefreshGrsPropertyPricesJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 100;
    public int $maxExceptions = 1;
    public int $timeout = 55;
    public int $uniqueFor = 14400;

    public function __construct(
        public int $scheduleId,
        public int $gdsId,
        public int $providerId,
        public int $days,
        public ?int $refreshStateId = null,
        public ?string $refreshCycleKey = null,
    ) {
        $this->onQueue('grs-prices');
    }

    public function uniqueId(): string
    {
        if ($this->refreshStateId !== null && $this->refreshCycleKey !== null) {
            return 'grs-property-price:'.$this->refreshStateId.':'.$this->refreshCycleKey;
        }

        return 'grs-property-price:'.$this->gdsId;
    }

    public function handle(
        HotelSyncService $service,
        GrsPriceRefreshScheduleService $schedules,
        ?ProviderDiagnosticAlertService $diagnostics = null,
        ?TelegramAlertService $alerts = null,
        ?ProviderRefreshCoordinator $coordinator = null,
    ): void {
        $diagnostics ??= app(ProviderDiagnosticAlertService::class);
        $alerts ??= app(TelegramAlertService::class);
        $coordinated = $this->refreshStateId !== null && $this->refreshCycleKey !== null;
        $cycleKey = (string) ($this->refreshCycleKey ?? '');

        if ($coordinated) {
            $coordinator ??= app(ProviderRefreshCoordinator::class);
            if ($coordinator->begin((int) $this->refreshStateId, $cycleKey) === null) {
                return;
            }
        }

        $provider = null;
        $adapter = null;
        $grsId = '';
        $hotelName = 'هتل #' . $this->gdsId;
        $from = null;
        $to = null;

        try {
            $provider = $schedules->providerById($this->providerId);
            if ($provider === null || $provider->code !== 'grs' || !$provider->is_active) {
                if ($coordinated) {
                    $coordinator->attempted(
                        (int) $this->refreshStateId,
                        $cycleKey,
                        ProviderRefreshOutcome::PROVIDER_DISABLED,
                    );
                    return;
                }
                throw new RuntimeException('GRS provider inactive, missing, or mismatched.');
            }

            if ($schedules->active($this->scheduleId, $this->gdsId) === null) {
                if ($coordinated) {
                    $coordinator->attempted(
                        (int) $this->refreshStateId,
                        $cycleKey,
                        ProviderRefreshOutcome::CYCLE_SUPERSEDED,
                    );
                }
                return;
            }

            $map = $schedules->mapForAccommodation($this->gdsId, (int) $provider->id);
            if (
                $map === null
                || $map->is_disabled === true
                || trim((string) $map->provider_property_id) === ''
            ) {
                if ($coordinated) {
                    $coordinator->attempted(
                        (int) $this->refreshStateId,
                        $cycleKey,
                        ProviderRefreshOutcome::MAP_UNAVAILABLE,
                    );
                }
                return;
            }

            $grsId = trim((string) $map->provider_property_id);
            $from = CarbonImmutable::today('Asia/Tehran');
            $to = $from->addDays($this->days);
            $started = now()->subSeconds(2);
            $adapter = new RateLimitedGrsAdapter($provider);
            $adapter->withRequestLogContext(null, self::class, 'handle');
            $adapter->trackAvailability(
                fn (): mixed => $schedules->requestStarted($this->scheduleId, $this->gdsId),
                fn (): mixed => $schedules->http200($this->scheduleId, $this->gdsId)
            );

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
                $currentMap = $schedules->mapForAccommodation($this->gdsId, (int) $provider->id);
                if (
                    $currentMap !== null
                    && $currentMap->is_disabled !== true
                    && trim((string) $currentMap->provider_property_id) !== ''
                ) {
                    SyncGrsHotelDetailsJob::dispatch(
                        (int) $provider->id,
                        (int) $currentMap->id
                    )->onQueue('grs-details');
                }

                if ($coordinated) {
                    $coordinator->retry(
                        (int) $this->refreshStateId,
                        $cycleKey,
                        ProviderRefreshOutcome::MAPPING_NOT_READY,
                        60,
                    );
                }
                return;
            }

            $verifiedRows = $schedules->verifiedRowCount(
                (int) $provider->id,
                (int) $map->id,
                $this->gdsId,
                $grsId,
                $adapter->lastAvailability,
                $started
            );

            if ($coordinated) {
                if ($verifiedRows > 0) {
                    $coordinator->done((int) $this->refreshStateId, $cycleKey);
                } else {
                    $coordinator->attempted(
                        (int) $this->refreshStateId,
                        $cycleKey,
                        ProviderRefreshOutcome::PROVIDER_EMPTY,
                    );
                }
                return;
            }

            // Compatibility path for jobs that were queued before coordinated provider state existed.
            $schedules->persisted($this->scheduleId, $this->gdsId);
        } catch (InvalidProviderAvailabilityDataException $e) {
            $hotelName = $this->resolveHotelName($schedules, $hotelName);

            $diagnostics->invalidGrsAvailability(
                $hotelName,
                $this->gdsId,
                $grsId,
                $e->issues,
                $adapter instanceof RateLimitedGrsAdapter
                    ? $adapter->lastAvailabilityExchange
                    : null,
            );

            if ($coordinated) {
                $coordinator->attempted(
                    (int) $this->refreshStateId,
                    $cycleKey,
                    ProviderRefreshOutcome::PROVIDER_INVALID_RESPONSE,
                );
                return;
            }

            $schedules->providerAnomalyHandled($this->scheduleId, $this->gdsId);
        } catch (GrsApiQuotaExceeded $e) {
            if ($coordinated) {
                $coordinator->retry(
                    (int) $this->refreshStateId,
                    $cycleKey,
                    ProviderRefreshOutcome::RATE_LIMITED,
                    max(1, $e->retryAfterSeconds),
                );
                return;
            }

            $this->release(max(1, $e->retryAfterSeconds));
        } catch (ConnectionException $e) {
            if ($coordinated) {
                $coordinator->attempted(
                    (int) $this->refreshStateId,
                    $cycleKey,
                    ProviderRefreshOutcome::PROVIDER_TIMEOUT,
                );
                return;
            }

            $this->fail($e);
        } catch (RequestException $e) {
            $hotelName = $this->resolveHotelName($schedules, $hotelName);
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

                if ($coordinated) {
                    $coordinator->retry(
                        (int) $this->refreshStateId,
                        $cycleKey,
                        ProviderRefreshOutcome::RATE_LIMITED,
                        $retryAfter,
                    );
                    return;
                }

                $this->release($retryAfter);
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
                $missingDates = $this->summarizeDateRanges($this->expectedDates($from, $to));

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

                $schedules->disableMapForAccommodation($this->gdsId, (int) $provider->id);

                if ($coordinated) {
                    $coordinator->attempted(
                        (int) $this->refreshStateId,
                        $cycleKey,
                        ProviderRefreshOutcome::PROVIDER_404,
                    );
                    return;
                }

                $schedules->providerAnomalyHandled($this->scheduleId, $this->gdsId);
                return;
            }

            if ($coordinated) {
                if ($status === 408) {
                    $coordinator->attempted(
                        (int) $this->refreshStateId,
                        $cycleKey,
                        ProviderRefreshOutcome::PROVIDER_TIMEOUT,
                    );
                    return;
                }

                // Authentication, request validation and similar 4xx failures mean
                // our request/configuration needs correction, so keep this cycle open.
                if ($status !== null && $status >= 400 && $status < 500) {
                    $alerts->internalFailure($e);
                    $coordinator->retry(
                        (int) $this->refreshStateId,
                        $cycleKey,
                        ProviderRefreshOutcome::REQUEST_ERROR,
                        300,
                    );
                    return;
                }

                $coordinator->attempted(
                    (int) $this->refreshStateId,
                    $cycleKey,
                    ProviderRefreshOutcome::PROVIDER_HTTP_ERROR,
                );
                return;
            }

            $this->fail($e);
        } catch (Throwable $e) {
            $alerts->internalFailure($e);

            if ($coordinated) {
                $coordinator->retry(
                    (int) $this->refreshStateId,
                    $cycleKey,
                    ProviderRefreshOutcome::INTERNAL_ERROR,
                    60,
                );
                return;
            }

            $this->fail($e);
        }
    }

    private function resolveHotelName(
        GrsPriceRefreshScheduleService $schedules,
        string $fallback,
    ): string {
        try {
            $accommodation = $schedules->accommodationById($this->gdsId);
            $name = trim((string) ($accommodation?->fa_name ?? ''))
                ?: trim((string) ($accommodation?->en_name ?? ''));

            return $name !== '' ? $name : $fallback;
        } catch (Throwable) {
            // Hotel name is optional diagnostic context and must never change the
            // provider refresh result or hide the original provider exception.
            return $fallback;
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
