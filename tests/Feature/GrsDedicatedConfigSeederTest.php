<?php

namespace Tests\Feature;

use App\Domain\Hotel\V2\GrsRefreshSettings;
use App\Models\Provider;
use Database\Seeders\GrsPriceRefreshConfigSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class GrsDedicatedConfigSeederTest extends TestCase
{
    public function test_dedicated_seeder_preserves_admin_values_and_other_suppliers(): void
    {
        Schema::dropIfExists('providers');
        Schema::create('providers', function (Blueprint $table): void {
            $table->id();
            $table->string('fa_name');
            $table->string('code')->unique();
            $table->json('config')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_online')->default(false);
            $table->timestamps();
        });

        Provider::query()->create([
            'fa_name' => 'GRS', 'code' => 'grs', 'is_online' => false,
            'config' => [
                'token' => 'existing-admin-token',
                'price_refresh' => ['dispatch_limit' => 2, 'scheduler_enabled' => true],
            ],
        ]);
        Provider::query()->create([
            'fa_name' => 'Other', 'code' => 'other', 'is_online' => true,
            'config' => ['price_refresh' => ['dispatch_limit' => 7]],
        ]);

        $this->seed(GrsPriceRefreshConfigSeeder::class);
        $this->seed(GrsPriceRefreshConfigSeeder::class);

        $grs = Provider::query()->where('code', 'grs')->firstOrFail();
        $other = Provider::query()->where('code', 'other')->firstOrFail();
        $this->assertSame('existing-admin-token', $grs->config['token']);
        $this->assertSame(2, data_get($grs->config, 'price_refresh.dispatch_limit'));
        $this->assertTrue(data_get($grs->config, 'price_refresh.scheduler_enabled'));
        $this->assertSame(GrsRefreshSettings::defaults()['default_days'], data_get($grs->config, 'price_refresh.default_days'));
        $this->assertFalse($grs->is_online);
        $this->assertTrue($other->is_online);
        $this->assertSame(['price_refresh' => ['dispatch_limit' => 7]], $other->config);
    }
}
