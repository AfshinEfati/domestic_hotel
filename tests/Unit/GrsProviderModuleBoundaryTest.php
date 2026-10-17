<?php

namespace Tests\Unit;

use App\Models\Provider;
use App\Modules\HotelProviders\V2\Grs\Application\GrsAccommodationMapRepairService;
use App\Modules\HotelProviders\V2\Grs\Application\GrsScheduledPriceRefresh;
use App\Modules\HotelProviders\V2\Grs\Application\Jobs\ProcessGrsHotelBatchJob;
use App\Modules\HotelProviders\V2\Grs\Application\Jobs\RefreshGrsPropertyPricesJob;
use App\Modules\HotelProviders\V2\Grs\Application\Jobs\RepairMissingGrsAccommodationMapsJob;
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
    public function test_registry_resolves_module_adapter(): void
    {
        $provider = $this->provider();
        $registry = $this->app->make(HotelProviderRegistry::class);

        $adapter = $registry->resolveAdapter($provider, $this->app);

        $this->assertInstanceOf(GrsAdapter::class, $adapter);
        $this->assertSame(GrsSettings::PROVIDER_CODE, $adapter->code());
        $this->assertSame(
            ['AvailableChanged', 'ReserveChanged', 'PropertyChanged', 'ReserveActivity'],
            $adapter->supportedWebhooks(),
        );
    }

    public function test_registry_uses_module_price_refresh_handler(): void
    {
        $provider = $this->provider();
        $registry = $this->app->make(HotelProviderRegistry::class);

        $this->assertSame(
            GrsScheduledPriceRefresh::class,
            $registry->priceRefreshHandler(GrsSettings::PROVIDER_CODE),
        );

        $handler = new GrsScheduledPriceRefresh();
        $this->assertTrue($handler->enabled($provider));
        $this->assertSame(120, $handler->hotelCapacityPerMinute($provider, 90));
    }

    public function test_module_settings_and_quota_use_provider_config(): void
    {
        $provider = $this->provider();

        $this->assertSame([
            'default_days' => 90,
            'api_cooldown_minutes' => 15,
            'scheduler_enabled' => true,
        ], GrsSettings::from($provider));
        $this->assertSame(120, GrsApiQuota::maxRequests($provider));
        $this->assertSame(1, GrsApiQuota::windowMinutes($provider));
    }

    public function test_module_repositories_resolve_directly(): void
    {
        $this->assertInstanceOf(
            GrsAvailabilityPersistenceRepository::class,
            $this->app->make(GrsAvailabilityPersistenceRepository::class),
        );
        $this->assertInstanceOf(
            GrsHotelDetailsRepository::class,
            $this->app->make(GrsHotelDetailsRepository::class),
        );
    }

    public function test_module_jobs_keep_expected_queue_contracts(): void
    {
        $refresh = new RefreshGrsPropertyPricesJob(1, 10001, 1, 30, 10, 'cycle');
        $this->assertSame('grs-property-price:10:cycle', $refresh->uniqueId());
        $this->assertSame('grs-prices', $refresh->queue);
        $this->assertSame(100, $refresh->tries);
        $this->assertSame(1, $refresh->maxExceptions);
        $this->assertSame(55, $refresh->timeout);
        $this->assertSame(14400, $refresh->uniqueFor);

        $due = new SyncGrsDuePricesJob(30);
        $this->assertSame('grs-due-prices-dispatch', $due->uniqueId());
        $this->assertSame('grs-prices', $due->queue);
        $this->assertSame(30, $due->days);
        $this->assertSame(1, $due->tries);
        $this->assertSame(60, $due->timeout);
        $this->assertSame(600, $due->uniqueFor);

        $catalog = new SyncGrsHotelCatalogJob();
        $this->assertSame('grs-hotel-catalog', $catalog->uniqueId());
        $this->assertSame(1, $catalog->tries);
        $this->assertSame(80, $catalog->timeout);
        $this->assertSame(3600, $catalog->uniqueFor);

        $details = new SyncGrsHotelDetailsJob(1, 2);
        $this->assertSame('grs-details:1:2', $details->uniqueId());
        $this->assertSame('grs-details', $details->queue);
        $this->assertSame(0, $details->tries);
        $this->assertSame(1, $details->maxExceptions);
        $this->assertSame(75, $details->timeout);
        $this->assertSame(43200, $details->uniqueFor);

        $batch = new ProcessGrsHotelBatchJob(1, []);
        $this->assertSame(1, $batch->providerId);
        $this->assertSame([], $batch->properties);
        $this->assertSame(1, $batch->tries);
        $this->assertSame(70, $batch->timeout);
    }

    public function test_module_map_repair_handles_empty_input_and_keeps_job_contract(): void
    {
        $repair = new GrsAccommodationMapRepairService();
        $provider = $this->provider();

        $this->assertSame([], $repair->missingAccommodationIds(1, []));
        $this->assertSame(
            ['repaired' => [], 'unresolved' => []],
            $repair->repair($provider, [], []),
        );

        $job = new RepairMissingGrsAccommodationMapsJob(1);
        $this->assertSame('grs-missing-accommodation-map-repair:1', $job->uniqueId());
        $this->assertSame('grs-hotels', $job->queue);
        $this->assertSame(20, $job->tries);
        $this->assertSame(55, $job->timeout);
        $this->assertSame(1800, $job->uniqueFor);
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
                    'api_cooldown_minutes' => 15,
                    'scheduler_enabled' => true,
                ],
            ],
        ]);
    }
}
