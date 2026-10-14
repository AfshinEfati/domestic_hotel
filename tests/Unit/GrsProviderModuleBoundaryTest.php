<?php

namespace Tests\Unit;

use App\Domain\Hotel\Providers\GRSAdapter as LegacyGrsAdapter;
use App\Domain\Hotel\Services\GrsScheduledPriceRefresh as LegacyGrsScheduledPriceRefresh;
use App\Models\Provider;
use App\Modules\HotelProviders\V2\Grs\Application\GrsScheduledPriceRefresh;
use App\Modules\HotelProviders\V2\Grs\Infrastructure\GrsAdapter;
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

    public function test_module_settings_are_exactly_legacy_settings_during_transition(): void
    {
        $provider = $this->provider();

        $this->assertSame(
            \App\Domain\Hotel\V2\GrsRefreshSettings::from($provider),
            GrsSettings::from($provider),
        );
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
