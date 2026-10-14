<?php

namespace App\Modules\HotelProviders\V2\Grs;

use App\Domain\Hotel\Repositories\GrsAvailabilityPersistenceRepository as LegacyGrsAvailabilityPersistenceRepository;
use App\Domain\Hotel\Repositories\GrsHotelDetailsRepository as LegacyGrsHotelDetailsRepository;
use App\Models\Provider;
use App\Modules\HotelProviders\V2\Grs\Application\GrsScheduledPriceRefresh;
use App\Modules\HotelProviders\V2\Grs\Infrastructure\GrsAdapter;
use App\Modules\HotelProviders\V2\Grs\Infrastructure\Persistence\GrsAvailabilityPersistenceRepository;
use App\Modules\HotelProviders\V2\Grs\Infrastructure\Persistence\GrsHotelDetailsRepository;
use App\Modules\HotelProviders\V2\Grs\Support\GrsSettings;
use App\Modules\HotelProviders\V2\Shared\HotelProviderRegistry;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\ServiceProvider;

final class GrsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            LegacyGrsAvailabilityPersistenceRepository::class,
            GrsAvailabilityPersistenceRepository::class,
        );

        $this->app->bind(
            LegacyGrsHotelDetailsRepository::class,
            GrsHotelDetailsRepository::class,
        );

        $this->app->afterResolving(
            HotelProviderRegistry::class,
            function (HotelProviderRegistry $registry): void {
                $registry->registerAdapter(
                    GrsSettings::PROVIDER_CODE,
                    static fn (Provider $provider, Container $container) => new GrsAdapter($provider),
                );

                $registry->registerPriceRefreshHandler(
                    GrsSettings::PROVIDER_CODE,
                    GrsScheduledPriceRefresh::class,
                );
            },
        );
    }
}
