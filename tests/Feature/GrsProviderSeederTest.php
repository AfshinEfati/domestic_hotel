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
    public function test_existing_provider_seeder_fills_missing_grs_keys_without_overriding_any_admin_settings(): void
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
        Provider::query()->create([
            'fa_name' => 'Parto', 'code' => 'parto',
            'config' => ['token' => 'parto-admin-token'],
            'is_active' => true, 'is_online' => true,
        ]);
        Provider::query()->create([
            'fa_name' => 'Snap', 'code' => 'snap',
            'config' => ['token' => 'snap-admin-token'],
            'is_active' => false, 'is_online' => true,
        ]);

        $this->seed(ProviderSeeder::class);
        $this->assertProviderState();

        $this->seed(ProviderSeeder::class);
        $this->assertProviderState();
    }

    private function assertProviderState(): void
    {
        $grs = Provider::query()->where('code', 'grs')->firstOrFail();
        $this->assertSame('administrator-token', $grs->config['token']);
        $this->assertSame(3, data_get($grs->config, 'availability_rate_limit.max_requests'));
        $this->assertSame(2, data_get($grs->config, 'price_refresh.dispatch_limit'));
        $this->assertTrue(data_get($grs->config, 'price_refresh.scheduler_enabled'));
        $this->assertSame(GrsRefreshSettings::defaults()['default_days'], data_get($grs->config, 'price_refresh.default_days'));
        $this->assertFalse($grs->is_active);
        $this->assertFalse($grs->is_online);

        $parto = Provider::query()->where('code', 'parto')->firstOrFail();
        $this->assertSame(['token' => 'parto-admin-token'], $parto->config);
        $this->assertTrue($parto->is_active);
        $this->assertTrue($parto->is_online);

        $snap = Provider::query()->where('code', 'snap')->firstOrFail();
        $this->assertSame(['token' => 'snap-admin-token'], $snap->config);
        $this->assertFalse($snap->is_active);
        $this->assertTrue($snap->is_online);
    }
}
