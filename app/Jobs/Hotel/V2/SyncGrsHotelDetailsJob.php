<?php

namespace App\Jobs\Hotel\V2;

use App\Domain\Hotel\Repositories\GrsHotelDetailsRepository;
use App\Exceptions\ProviderDataException;
use App\Domain\Hotel\V2\GrsHotelDetailsClient;
use App\Services\Alerts\TelegramAlertService;
use App\Services\HotelChildPolicyTextParser;
use DateTimeInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use RuntimeException;

/** No availability, price schedule, room calendar or shared GRS API quota. */
class SyncGrsHotelDetailsJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 0; // Releases must not exhaust the attempt counter.
    public int $maxExceptions = 1; // HTTP/DB errors fail this property; no blind retries.
    public int $timeout = 75; // Below its Horizon timeout (80) and Redis retry_after (90).
    public int $uniqueFor = 43200;

    public function __construct(public int $providerId, public int $mapId)
    {
        $this->onQueue('grs-details');
    }

    public function uniqueId(): string
    {
        return 'grs-details:'.$this->providerId.':'.$this->mapId;
    }

    public function retryUntil(): DateTimeInterface
    {
        return now()->addHours(12);
    }

    public function handle(
        GrsHotelDetailsRepository $repository,
        GrsHotelDetailsClient $client,
        HotelChildPolicyTextParser $parser,
        TelegramAlertService $alerts
    ): void {
        $provider = $repository->grsProvider();
        if ($provider === null || (int) $provider->id !== $this->providerId
            || !$provider->is_active || !$provider->is_online) {
            throw new RuntimeException('GRS details provider is missing, inactive, offline or changed.');
        }
        $map = $repository->mappedHotel($this->providerId, $this->mapId);
        if ($map === null || $map->accommodation === null) {
            throw new RuntimeException('GRS details mapping or local hotel no longer exists.');
        }

        // Separate from the availability quota. Six-second dispatch spacing
        // plus this independent counter prevent catch-up bursts after downtime.
        try {
            $wait = Cache::store('redis')->lock('grs-v2-details-request-lock', 10)
                ->block(5, static function (): int {
                    $key = 'grs-v2-details-requests';
                    if (RateLimiter::tooManyAttempts($key, 10)) {
                        return max(1, RateLimiter::availableIn($key));
                    }
                    RateLimiter::hit($key, 60);
                    return 0;
                });
        } catch (LockTimeoutException) {
            $this->release(10);
            return;
        }
        if ($wait > 0) {
            $this->release($wait + 1);
            return;
        }

        $propertyId = trim((string) $map->provider_property_id);

        try {
            $details = $client->fetch($provider, $propertyId);
        } catch (ProviderDataException $exception) {
            Log::warning('GRS hotel details skipped because provider data is incomplete', [
                'provider_id' => $this->providerId,
                'accommodation_id' => $map->accommodation_id,
                'provider_property_id' => $propertyId,
                'reason' => $exception->getMessage(),
            ]);

            $alerts->providerDataIssue(
                provider: 'grs',
                operation: 'GET /v1/properties/{id}',
                resourceId: $propertyId,
                reason: $exception->getMessage(),
            );

            return;
        }

        try {
            $repository->persist($map, $details, $parser);
        } catch (LockTimeoutException) {
            // Do not fail a hotel simply because another details worker is
            // creating shared facility dictionary entries at the same time.
            $this->release(10);
            return;
        }

        Log::info('GRS hotel details refreshed', [
            'provider_id' => $this->providerId,
            'accommodation_id' => $map->accommodation_id,
            'provider_property_id' => $propertyId,
            'room_types' => count($details['room_types']),
        ]);
    }
}
