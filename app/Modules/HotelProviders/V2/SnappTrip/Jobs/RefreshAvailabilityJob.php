<?php

namespace App\Modules\HotelProviders\V2\SnappTrip\Jobs;

use App\Domain\Hotel\Services\ProviderRefreshCoordinator;
use App\Domain\Hotel\Support\ProviderRefreshOutcome;
use App\Modules\HotelProviders\V2\Shared\ProviderOutboundGuard;
use App\Modules\HotelProviders\V2\SnappTrip\Application\RefreshScheduledAvailability;
use App\Modules\HotelProviders\V2\SnappTrip\Exceptions\SnappTripRateLimitExceeded;
use App\Modules\HotelProviders\V2\SnappTrip\Support\SnappTripSettings;
use App\Repositories\Contracts\AccommodationProviderMapRepositoryInterface;
use App\Services\Alerts\TelegramAlertService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

final class RefreshAvailabilityJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 30;
    public int $timeout = 90;
    public int $uniqueFor = 21600;

    // These properties were added after the first SnappTrip jobs had already been
    // queued. Keep real property defaults (instead of constructor-promotion defaults)
    // so an older serialized payload cannot leave them as uninitialized typed props.
    public ?int $refreshStateId = null;
    public ?string $refreshCycleKey = null;

    public function __construct(
        public int $scheduleId,
        public int $accommodationId,
        public int $providerId,
        public int $days,
        ?int $refreshStateId = null,
        ?string $refreshCycleKey = null,
    ) {
        $this->refreshStateId = $refreshStateId;
        $this->refreshCycleKey = $refreshCycleKey;
        $this->onQueue('snapptrip-prices');
    }

    public function uniqueId(): string
    {
        // isset() is intentionally used here: it is safe even for a legacy payload
        // deserialized from before these typed properties existed.
        if (isset($this->refreshStateId, $this->refreshCycleKey)) {
            return 'snapptrip-v2-refresh:'.$this->refreshStateId.':'.$this->refreshCycleKey;
        }

        return 'snapptrip-v2-refresh:'.$this->accommodationId;
    }

    public function handle(
        ProviderOutboundGuard $guard,
        RefreshScheduledAvailability $refresh,
        AccommodationProviderMapRepositoryInterface $maps,
        ProviderRefreshCoordinator $coordinator,
        TelegramAlertService $alerts,
    ): void {
        // Backward-compatible direct invocation is kept only for jobs already present
        // in a queue during deployment. New scheduler jobs always carry both state ID
        // and cycle key so an old job can never mutate a newer hotel/provider cycle.
        if (!isset($this->refreshStateId, $this->refreshCycleKey)) {
            if (!$guard->allows(SnappTripSettings::PROVIDER_CODE)) {
                return;
            }
            try {
                $refresh->execute($this->scheduleId, $this->accommodationId, $this->providerId, $this->days);
            } catch (SnappTripRateLimitExceeded $exception) {
                $this->release($exception->retryAfterSeconds);
            }
            return;
        }

        $refreshStateId = $this->refreshStateId;
        $cycleKey = $this->refreshCycleKey;
        $state = $coordinator->begin($refreshStateId, $cycleKey);
        if ($state === null) {
            return;
        }

        if (!$guard->allows(SnappTripSettings::PROVIDER_CODE)) {
            $coordinator->attempted(
                $refreshStateId,
                $cycleKey,
                ProviderRefreshOutcome::PROVIDER_DISABLED,
            );
            return;
        }

        try {
            $rowCount = $refresh->execute(
                $this->scheduleId,
                $this->accommodationId,
                $this->providerId,
                $this->days,
            );

            $map = $maps->findForAccommodationAndProvider($this->accommodationId, $this->providerId);
            if ($map === null || $map->is_disabled) {
                $coordinator->attempted(
                    $refreshStateId,
                    $cycleKey,
                    ProviderRefreshOutcome::PROVIDER_404,
                );
                return;
            }

            if ($rowCount < 1) {
                $coordinator->attempted(
                    $refreshStateId,
                    $cycleKey,
                    ProviderRefreshOutcome::PROVIDER_EMPTY,
                );
                return;
            }

            $coordinator->done($refreshStateId, $cycleKey);
        } catch (SnappTripRateLimitExceeded $exception) {
            $coordinator->retry(
                $refreshStateId,
                $cycleKey,
                ProviderRefreshOutcome::RATE_LIMITED,
                max(1, $exception->retryAfterSeconds),
            );
        } catch (ConnectionException) {
            $coordinator->attempted(
                $refreshStateId,
                $cycleKey,
                ProviderRefreshOutcome::PROVIDER_TIMEOUT,
            );
        } catch (RequestException $exception) {
            $status = $exception->response?->status();
            if ($status === 429) {
                $coordinator->retry(
                    $refreshStateId,
                    $cycleKey,
                    ProviderRefreshOutcome::RATE_LIMITED,
                    60,
                );
                return;
            }

            if ($status !== null && $status >= 400 && $status < 500 && !in_array($status, [404, 408], true)) {
                $alerts->internalFailure($exception);
                $coordinator->retry(
                    $refreshStateId,
                    $cycleKey,
                    ProviderRefreshOutcome::REQUEST_ERROR,
                    300,
                );
                return;
            }

            $coordinator->attempted(
                $refreshStateId,
                $cycleKey,
                ProviderRefreshOutcome::PROVIDER_HTTP_ERROR,
            );
        } catch (Throwable $exception) {
            $alerts->internalFailure($exception);
            $coordinator->retry(
                $refreshStateId,
                $cycleKey,
                ProviderRefreshOutcome::INTERNAL_ERROR,
                60,
            );
        }
    }
}
