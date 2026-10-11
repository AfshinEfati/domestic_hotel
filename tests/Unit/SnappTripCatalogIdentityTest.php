<?php

namespace Tests\Unit;

use App\Domain\Hotel\Repositories\CityRepository;
use App\Domain\Hotel\Services\AccommodationTypeResolver;
use App\Models\Accommodation;
use App\Models\Provider;
use App\Modules\HotelProviders\V2\SnappTrip\Infrastructure\Persistence\SnappTripCatalogRepository;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use ReflectionClass;
use Tests\TestCase;

class SnappTripCatalogIdentityTest extends TestCase
{
    public function test_existing_canonical_hotel_is_reused_when_persian_digits_differ(): void
    {
        $this->createFixture();

        DB::table('provider_city_maps')->insert([
            'provider_id' => 4,
            'provider_city_id' => 'qeshm',
            'city_id' => 151,
        ]);

        DB::table('accommodations')->insert([
            'id' => 77,
            'city_id' => 151,
            'fa_name' => 'مارینا 1',
            'en_name' => 'Marina 1',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $provider = new Provider([
            'id' => 4,
            'code' => 'snap',
            'is_active' => true,
        ]);
        $provider->exists = true;

        $catalog = new SnappTripCatalogRepository(
            (new ReflectionClass(CityRepository::class))->newInstanceWithoutConstructor(),
            (new ReflectionClass(AccommodationTypeResolver::class))->newInstanceWithoutConstructor(),
        );

        $map = $catalog->persistHotelBundle($provider, [
            'provider_property_id' => '3810',
            'provider_city_id' => 'qeshm',
            'fa_name' => 'مارینا ۱',
            'en_name' => 'Marina 1',
            'enabled' => true,
            'policies' => [],
        ]);

        $this->assertSame(77, (int) $map->accommodation_id);
        $this->assertSame(1, Accommodation::query()->count());
        $this->assertDatabaseHas('accommodation_provider_maps', [
            'provider_id' => 4,
            'provider_property_id' => '3810',
            'accommodation_id' => 77,
        ]);
    }

    private function createFixture(): void
    {
        Schema::dropIfExists('accommodation_provider_details');
        Schema::dropIfExists('accommodation_provider_maps');
        Schema::dropIfExists('provider_city_maps');
        Schema::dropIfExists('accommodations');

        Schema::create('accommodations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('city_id');
            $table->string('fa_name');
            $table->string('en_name')->nullable();
            $table->unsignedBigInteger('accommodation_type_id')->nullable();
            $table->unsignedTinyInteger('star')->nullable();
            $table->string('grade')->nullable();
            $table->text('address')->nullable();
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['city_id', 'fa_name']);
        });

        Schema::create('provider_city_maps', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('provider_id');
            $table->string('provider_city_id');
            $table->unsignedBigInteger('city_id');
            $table->timestamps();
        });

        Schema::create('accommodation_provider_maps', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('accommodation_id');
            $table->unsignedBigInteger('provider_id');
            $table->string('provider_property_id');
            $table->boolean('is_disabled')->default(false);
            $table->string('fa_name')->nullable();
            $table->string('en_name')->nullable();
            $table->timestamps();
            $table->unique(['provider_id', 'provider_property_id']);
        });

        Schema::create('accommodation_provider_details', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('accommodation_provider_map_id')->unique();
            $table->string('accommodation_title')->nullable();
            $table->text('description')->nullable();
            $table->string('provider_url')->nullable();
            $table->boolean('is_marketplace')->nullable();
            $table->string('check_in_time')->nullable();
            $table->string('check_out_time')->nullable();
            $table->text('cancellation_policy')->nullable();
            $table->boolean('foreigners_fee')->nullable();
            $table->text('free_transfer_policy')->nullable();
            $table->json('free_transfers')->nullable();
            $table->unsignedSmallInteger('calendar_window_days')->nullable();
            $table->unsignedSmallInteger('refresh_horizon_days')->nullable();
            $table->timestamps();
        });
    }
}
