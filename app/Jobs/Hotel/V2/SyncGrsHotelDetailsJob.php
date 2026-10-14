<?php

namespace App\Jobs\Hotel\V2;

use App\Domain\Hotel\Repositories\GrsHotelDetailsRepository;
use App\Exceptions\ProviderDataException;
use App\Modules\HotelProviders\V2\Grs\Infrastructure\GrsHotelDetailsClient;
use App\Modules\HotelProviders\V2\Grs\Support\GrsApiQuota;
use App\Modules\HotelProviders\V2\Grs\Support\GrsApiQuotaExceeded;
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

    public int $tries = 0;
    public int $maxExceptions = 1;
    public int $timeout = 75;
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
            || !$provider->is_active) {
            throw new RuntimeException('GRS details provider is missing, inactive or changed.');
        }

        $map = $repository->mappedHotel($this->providerId, $this->mapId);
        if ($map === null || $map->accommodation === null) {
            throw new RuntimeException('GRS details mapping or local hotel no longer exists.');
        }

        try {
            GrsApiQuota::acquire($provider);
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
            $this->release(10);
            return;
        }
    }
}
