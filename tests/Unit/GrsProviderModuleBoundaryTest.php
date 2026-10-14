<?php

namespace Tests\Unit;

use App\Domain\Hotel\Providers\GRSAdapter as LegacyGrsAdapter;
use App\Domain\Hotel\Repositories\GrsAvailabilityPersistenceRepository as LegacyGrsAvailabilityPersistenceRepository;
use App\Domain\Hotel\Repositories\GrsHotelDetailsRepository as LegacyGrsHotelDetailsRepository;
use App\Domain\Hotel\Services\GrsScheduledPriceRefresh as LegacyGrsScheduledPriceRefresh;
use App\Domain\Hotel\V2\GrsApiQuota as LegacyGrsApiQuota;
use App\Domain\Hotel\V2\GrsRefreshSettings as LegacyGrsRefreshSettings;
use App\Jobs\Hotel\V2\ProcessGrsHotelBatchJob as LegacyProcessGrsHotelBatchJob;
use App\Jobs\Hotel\V2\RefreshGrsPropertyPricesJob as LegacyRefreshGrsPropertyPricesJob;
use App\Jobs\Hotel\V2\SyncGrsDuePricesJob as LegacySyncGrsDuePricesJob;
use App\Jobs\Hotel\V2\SyncGrsHotelCatalogJob as LegacySyncGrsHotelCatalogJob;
use App\Jobs\Hotel\V2\SyncGrsHotelDetailsJob as LegacySyncGrsHotelDetailsJob;
use App\Models\Provider;
use App\Modules\HotelProviders\V2\Grs\Application\GrsScheduledPriceRefresh;
use App\Modules\HotelProviders\V2\Grs\Application\Jobs\ProcessGrsHotelBatchJob;
use App\Modules\HotelProviders\V2\Grs\Application\Jobs\RefreshGrsPropertyPricesJob;
use App\Modules\HotelProviders\V2\Grs\Application\Jobs\SyncGrsDuePricesJob;
use App\Modules\HotelProviders\V2\Grs\Application\Jobs\SyncGrsHotelCatalogJob;
use App\Modules\HotelProviders\V2\Grs\Application\Jobs\SyncGrsHotelDetailsJob;
use App\Modules\HotelProviders\V2\Grs\Infrastructure\GrsAdapter;
use App\Modules\HotelProviders\V2\Grs\Infrastructure\Persistence\GrsAvailabilityPersistenceRepository;
use App\Modules\HotelProviders\V2\Grs\Infrastructure\Persistence\GrsHotelDetailsRepository;
use App\Modules\HotelProviders\V2\Grs\Support\GrsApiQuota;
use App\Modules\HotelProviders\V2\Grs\Support\GrsSettings;
use App\Modules\HotelProviders\V2\Shared\HotelProviderRegistry;
use Tests\TestCase;

final class GrsProviderModuleBoundaryTest extends TestCase
{
    public function test_registry_resolves_grs_through_module_without_changing_legacy_adapter_behavior(): void
    {
        $provider = $this->provider();
        $registry = $this->app->make(HotelProviderRegistry::class);

        $adapter = $registry->resolveAdapter($provider, $this->app);

        $this->assertInstanceOf(GrsAdapter::class, $adapter);
        $this->assertInstanceOf(LegacyGrsAdapter::class, $adapter);
        $this->assertSame('grs', $adapter->code());
        $this->assertSame(
            (new LegacyGrsAdapter($provider))->supportedWebhooks(),
            $adapter->supportedWebhooks(),
        );
    }

    public function test_registry_uses_module_price_refresh_handler_with_legacy_parity(): void
    {
        $provider = $this->provider();
        $registry = $this->app->make(HotelProviderRegistry::class);

        $this->assertSame(
            GrsScheduledPriceRefresh::class,
            $registry->priceRefreshHandler(GrsSettings::PROVIDER_CODE),
        );

        $legacy = new LegacyGrsScheduledPriceRefresh();
        $module = new GrsScheduledPriceRefresh();

        $this->assertSame($legacy->enabled($provider), $module->enabled($provider));
        $this->assertSame(
            $legacy->hotelCapacityPerMinute($provider, 90),
            $module->hotelCapacityPerMinute($provider, 90),
        );
    }

    public function test_module_settings_and_quota_match_legacy_behavior(): void
    {
        $provider = $this->provider();

        $this->assertSame(
            LegacyGrsRefreshSettings::from($provider),
            GrsSettings::from($provider),
        );
        $this->assertSame(
            LegacyGrsApiQuota::maxRequests($provider),
            GrsApiQuota::maxRequests($provider),
        );
        $this->assertSame(
            LegacyGrsApiQuota::windowMinutes($provider),
            GrsApiQuota::windowMinutes($provider),
        );
    }

    public function test_legacy_repository_types_resolve_to_module_implementations(): void
    {
        $availability = $this->app->make(LegacyGrsAvailabilityPersistenceRepository::class);
        $details = $this->app->make(LegacyGrsHotelDetailsRepository::class);

        $this->assertInstanceOf(GrsAvailabilityPersistenceRepository::class, $availability);
        $this->assertInstanceOf(LegacyGrsAvailabilityPersistenceRepository::class, $availability);
        $this->assertInstanceOf(GrsHotelDetailsRepository::class, $details);
        $this->assertInstanceOf(LegacyGrsHotelDetailsRepository::class, $details);
    }

    public function test_module_jobs_preserve_legacy_job_contracts(): void
    {
        $this->assertTrue(is_subclass_of(RefreshGrsPropertyPricesJob::class, LegacyRefreshGrsPropertyPricesJob::class));
        $this->assertTrue(is_subclass_of(SyncGrsDuePricesJob::class, LegacySyncGrsDuePricesJob::class));
        $this->assertTrue(is_subclass_of(SyncGrsHotelCatalogJob::class, LegacySyncGrsHotelCatalogJob::class));
        $this->assertTrue(is_subclass_of(SyncGrsHotelDetailsJob::class, LegacySyncGrsHotelDetailsJob::class));
        $this->assertTrue(is_subclass_of(ProcessGrsHotelBatchJob::class, LegacyProcessGrsHotelBatchJob::class));
    }

    private function provider(): Provider
    {
        return (new Provider())->forceFill([
            'id' => 1,
            'code' => GrsSettings::PROVIDER_CODE,
            'is_active' => true,
            'is_online' => true,
            'config' => [
                'base_url' => 'https://example.test',
                'token' => 'test-token',
                'availability_rate_limit' => [
                    'max_requests' => 120,
                    'window_minutes' => 1,
                ],
                'price_refresh' => [
                    'default_days' => 90,
                    'scheduler_enabled' => true,
                ],
            ],
        ]);
    }
}
