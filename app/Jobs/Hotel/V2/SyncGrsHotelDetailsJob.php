<?php

namespace App\Jobs\Hotel\V2;

use App\Domain\Hotel\Repositories\GrsHotelDetailsRepository;
use App\Domain\Hotel\V2\GrsApiQuota;
use App\Domain\Hotel\V2\GrsApiQuotaExceeded;
use App\Domain\Hotel\V2\GrsHotelDetailsClient;
use App\Exceptions\ProviderDataException;
use App\Services\HotelChildPolicyTextParser;
use DateTimeInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\RequestException;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use RuntimeException;

/** GRS details sync shares the same provider-wide background API quota as availability. */
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
        HotelChildPolicyTextParser $parser
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

        try {
            GrsApiQuota::acquire();
        } catch (GrsApiQuotaExceeded $e) {
            $this->release(max(1, $e->retryAfterSeconds));
            return;
        }

        $propertyId = trim((string) $map->provider_property_id);

        try {
            $details = $client->fetch($provider, $propertyId);
        } catch (ProviderDataException) {
            return;
        } catch (RequestException $e) {
            if ($e->response?->status() === 429) {
                GrsApiQuota::registerProvider429();
                $this->release(max(1, GrsApiQuota::cooldownSeconds()));
                return;
            }

            throw $e;
        }

        try {
            $repository->persist($map, $details, $parser);
        } catch (LockTimeoutException) {
            // Do not fail a hotel simply because another details worker is
            // creating shared facility dictionary entries at the same time.
            $this->release(10);
            return;
        }
    }
}
