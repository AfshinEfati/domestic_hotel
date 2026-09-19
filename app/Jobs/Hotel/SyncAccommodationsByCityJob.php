<?php

namespace App\Jobs\Hotel;

use App\Domain\Hotel\Contracts\ProviderAdapterInterface;
use App\Domain\Hotel\Services\AccommodationTypeResolver;
use App\Repositories\Contracts\AccommodationProviderMapRepositoryInterface;
use App\Repositories\Contracts\AccommodationRepositoryInterface;
use App\Repositories\Contracts\CityRepositoryInterface;
use App\Repositories\Contracts\FacilityGroupRepositoryInterface;
use App\Repositories\Contracts\FacilityRepositoryInterface;
use App\Repositories\Contracts\ProviderRepositoryInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\RateLimiter;

class SyncAccommodationsByCityJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    private const RATE_LIMITER_PREFIX = 'accommodations-sync';
    private const RATE_LIMITER_DECAY_SECONDS = 60;
    private const DEFAULT_REQUESTS_PER_MINUTE = 5;
    private const OVERLAP_RELEASE_AFTER_SECONDS = 10;
    private const OVERLAP_EXPIRE_AFTER_SECONDS = 3600;
    public function __construct(
        public string $providerCode,
        public string $providerCityId,
        public int $cityId
    ) {
    }

    /**
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [
            (new WithoutOverlapping($this->overlapKey()))
                ->releaseAfter(self::OVERLAP_RELEASE_AFTER_SECONDS)
                ->expireAfter(self::OVERLAP_EXPIRE_AFTER_SECONDS),
        ];
    }

    /**
     * @throws BindingResolutionException
     */
    public function handle(
        ProviderRepositoryInterface $providerRepo,
        CityRepositoryInterface $cityRepo,
        AccommodationTypeResolver $types,
        AccommodationRepositoryInterface $accRepo,
        AccommodationProviderMapRepositoryInterface $mapRepo,
        FacilityGroupRepositoryInterface $facilityGroupRepo,
        FacilityRepositoryInterface $facilityRepo
    ): void {
        $provider = $providerRepo->findDynamic(where: ['code' => $this->providerCode]);
        if (!$provider) {
            return;
        }

        $config = is_array($provider->config ?? null) ? $provider->config : [];
        $requestsPerMinute = $this->resolveRequestsPerMinute($config);

        if ($this->shouldDelayForRateLimit((int)$provider->id, $requestsPerMinute)) {
            return;
        }

        $city = $cityRepo->find($this->cityId);
        if (!$city) {
            return;
        }

        if ($requestsPerMinute > 0) {
            RateLimiter::hit(
                $this->rateLimiterKey((int)$provider->id),
                self::RATE_LIMITER_DECAY_SECONDS
            );
        }

        /** @var ProviderAdapterInterface $adapter */
        $adapter = app()->make(ProviderAdapterInterface::class, ['provider' => $provider]);
        $properties = $adapter->fetchPropertiesByCity($this->providerCityId);

        foreach ($properties as $p) {
            // Resolve to one of the seeded types, or the single unknown type.
            $typeId = $types->resolveId($p['type'] ?? null, $p['type_en'] ?? null);

            $acc = $accRepo->updateOrCreate(
                [
                    'city_id' => $city->id,
                    'fa_name' => $p['name'],
                ],
                [
                    'en_name' => $p['name_en'] ?? null,
                    'accommodation_type_id' => $typeId,
                    'star' => (int)($p['star'] ?? 0),
                    'grade' => $p['grade'] ?? null,
                    'address' => $p['address'] ?? null,
                    'lat' => ($p['latitude'] && abs($p['latitude']) <= 90) ? $p['latitude'] : null,
                    'lng' => ($p['longitude'] && abs($p['longitude']) <= 180) ? $p['longitude'] : null,
                    'is_active' => true,
                ]
            );

            $mapRepo->updateOrCreate(
                [
                    'provider_id' => $provider->id,
                    'provider_property_id' => (string)$p['id'],
                ],
                [
                    'accommodation_id' => $acc->id,
                    'fa_name' => $p['name'] ?? null,
                    'en_name' => $p['name_en'] ?? null,
                    // updated_at/created_at handled by Eloquent
                ]
            );

            if (!empty($p['facilities'])) {
                $facilityIds = [];
                foreach ($p['facilities'] as $f) {
                    $group = $facilityGroupRepo->firstOrCreate(
                        ['fa_name' => $f['group_name'] ?? 'Ø³Ø§ÛŒØ±'],
                        ['en_name' => $f['group_name_en'] ?? null]
                    );

                    $facility = $facilityRepo->updateOrCreate(
                        [
                            'fa_name' => $f['name'],
                            'facility_group_id' => $group->id,
                        ],
                        ['en_name' => $f['name_en'] ?? null]
                    );

                    $facilityIds[$facility->id] = ['description' => $f['description'] ?? ''];
                }

                $acc->facilities()->sync($facilityIds);
            }
        }
    }

    private function overlapKey(): string
    {
        return 'accommodations-sync:provider:' . $this->providerCode;
    }

    private function rateLimiterKey(int $providerId): string
    {
        return self::RATE_LIMITER_PREFIX . ':provider-' . $providerId;
    }

    private function shouldDelayForRateLimit(int $providerId, int $requestsPerMinute): bool
    {
        if ($requestsPerMinute <= 0) {
            return false;
        }

        $limiterKey = $this->rateLimiterKey($providerId);
        if (!RateLimiter::tooManyAttempts($limiterKey, $requestsPerMinute)) {
            return false;
        }

        $availableInSeconds = RateLimiter::availableIn($limiterKey);
        $delaySeconds = $this->determineRateLimitDelaySeconds($availableInSeconds, $requestsPerMinute);
        $this->release($delaySeconds);

        return true;
    }

    private function determineRateLimitDelaySeconds(?int $availableInSeconds, int $requestsPerMinute): int
    {
        $baseDelaySeconds = $this->calculateMinimumIntervalSeconds($requestsPerMinute);

        if ($availableInSeconds === null) {
            return $baseDelaySeconds;
        }

        $availableInSeconds = (int)max(0, $availableInSeconds);

        if ($availableInSeconds === 0) {
            return $baseDelaySeconds;
        }

        return max($baseDelaySeconds, $availableInSeconds);
    }

    private function calculateMinimumIntervalSeconds(int $requestsPerMinute): int
    {
        if ($requestsPerMinute <= 0) {
            return 1;
        }

        return (int)max(1, ceil(60 / $requestsPerMinute));
    }

    private function resolveRequestsPerMinute(array $config): int
    {
        $value = $config['accommodations_requests_per_minute'] ?? $config['requests_per_minute'] ?? null;

        return $this->resolvePositiveInt($value, self::DEFAULT_REQUESTS_PER_MINUTE, 1);
    }

    private function resolvePositiveInt(mixed $value, int $default, int $min): int
    {
        if (is_numeric($value)) {
            $intValue = (int)$value;
            if ($intValue >= $min) {
                return $intValue;
            }
        }

        return max($default, $min);
    }
}
