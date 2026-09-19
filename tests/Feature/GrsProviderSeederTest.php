<?php

namespace Tests\Feature;

use App\Domain\Hotel\V2\GrsRefreshSettings;
use App\Models\Provider;
use Database\Seeders\ProviderSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class GrsProviderSeederTest extends TestCase
{
    public function test_seeding_fills_missing_grs_keys_without_overriding_admin_settings(): void
    {
        Schema::dropIfExists('providers');
        Schema::create('providers', function (Blueprint $table): void {
            $table->id();
            $table->string('fa_name');
            $table->string('en_name')->nullable();
            $table->string('class')->nullable();
            $table->string('code')->unique();
            $table->json('config')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_online')->default(false);
            $table->timestamps();
        });

        Provider::query()->create([
            'fa_name' => 'GRS',
            'code' => 'grs',
            'config' => [
                'token' => 'administrator-token',
                'availability_rate_limit' => ['max_requests' => 3],
                'price_refresh' => ['dispatch_limit' => 2, 'scheduler_enabled' => true],
            ],
            'is_active' => false,
            'is_online' => false,
        ]);

        $this->seed(ProviderSeeder::class);
        $provider = Provider::query()->where('code', 'grs')->firstOrFail();
        $this->assertSame('administrator-token', $provider->config['token']);
        $this->assertSame(3, data_get($provider->config, 'availability_rate_limit.max_requests'));
        $this->assertSame(2, data_get($provider->config, 'price_refresh.dispatch_limit'));
        $this->assertTrue(data_get($provider->config, 'price_refresh.scheduler_enabled'));
        $this->assertSame(GrsRefreshSettings::defaults()['default_days'], data_get($provider->config, 'price_refresh.default_days'));
        $this->assertFalse($provider->is_active);
        $this->assertFalse($provider->is_online);

        $this->seed(ProviderSeeder::class);
        $provider->refresh();
        $this->assertSame(2, data_get($provider->config, 'price_refresh.dispatch_limit'));
        $this->assertTrue(data_get($provider->config, 'price_refresh.scheduler_enabled'));
        $this->assertSame('administrator-token', $provider->config['token']);
        $this->assertFalse($provider->is_online);
    }
}
