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

    public function __construct(
        public int $scheduleId,
        public int $accommodationId,
        public int $providerId,
        public int $days,
        public ?int $refreshStateId = null,
    ) {
        $this->onQueue('snapptrip-prices');
    }

    public function uniqueId(): string
    {
        return 'snapptrip-v2-refresh:'.($this->refreshStateId ?? $this->accommodationId);
    }

    public function handle(
        ProviderOutboundGuard $guard,
        RefreshScheduledAvailability $refresh,
        AccommodationProviderMapRepositoryInterface $maps,
        ProviderRefreshCoordinator $coordinator,
        TelegramAlertService $alerts,
    ): void {
        // Backward-compatible direct invocation is kept only for old/manual jobs
        // already present in a queue during deployment. New scheduler jobs always
        // carry refreshStateId and are coordinated through local provider state.
        if ($this->refreshStateId === null) {
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

        $state = $coordinator->begin($this->refreshStateId);
        if ($state === null) {
            return;
        }

        if (!$guard->allows(SnappTripSettings::PROVIDER_CODE)) {
            $coordinator->attempted($this->refreshStateId, ProviderRefreshOutcome::PROVIDER_DISABLED);
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
                $coordinator->attempted($this->refreshStateId, ProviderRefreshOutcome::PROVIDER_404);
                return;
            }

            if ($rowCount < 1) {
                $coordinator->attempted($this->refreshStateId, ProviderRefreshOutcome::PROVIDER_EMPTY);
                return;
            }

            $coordinator->done($this->refreshStateId);
        } catch (SnappTripRateLimitExceeded $exception) {
            $coordinator->retry(
                $this->refreshStateId,
                ProviderRefreshOutcome::RATE_LIMITED,
                max(1, $exception->retryAfterSeconds),
            );
        } catch (ConnectionException) {
            // A provider that cannot answer this otherwise-valid outbound request has
            // been attempted for this cycle. It must not block the hotel indefinitely.
            $coordinator->attempted($this->refreshStateId, ProviderRefreshOutcome::PROVIDER_TIMEOUT);
        } catch (RequestException $exception) {
            if ($exception->response?->status() === 429) {
                $coordinator->retry($this->refreshStateId, ProviderRefreshOutcome::RATE_LIMITED, 60);
                return;
            }

            $coordinator->attempted($this->refreshStateId, ProviderRefreshOutcome::PROVIDER_HTTP_ERROR);
        } catch (Throwable $exception) {
            $alerts->internalFailure($exception);
            $coordinator->retry($this->refreshStateId, ProviderRefreshOutcome::INTERNAL_ERROR, 60);
        }
    }
}
