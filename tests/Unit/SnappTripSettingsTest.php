<?php

namespace Tests\Unit;

use App\Modules\HotelProviders\V2\SnappTrip\Support\SnappTripSettings;
use PHPUnit\Framework\TestCase;

class SnappTripSettingsTest extends TestCase
{
    public function test_documented_rate_limit_and_safe_defaults_are_registered(): void
    {
        $settings = SnappTripSettings::defaults();

        $this->assertSame('https://b2bapiv2.snapptrip.com/', $settings['base_url']);
        $this->assertSame('api_key_snapptrip', $settings['api_key']);
        $this->assertSame(120, data_get($settings, 'rate_limit.max_requests'));
        $this->assertSame(1, data_get($settings, 'rate_limit.window_minutes'));
        $this->assertTrue(data_get($settings, 'static_sync.scheduler_enabled'));
        $this->assertTrue(data_get($settings, 'price_refresh.scheduler_enabled'));
        $this->assertFalse(data_get($settings, 'purchase.online_enabled'));
    }
}
