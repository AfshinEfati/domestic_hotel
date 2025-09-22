<?php

namespace Tests\Unit\Models;

use App\Models\Provider;
use App\Models\ProviderCityMap;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class ProviderTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function test_city_maps_relation_returns_has_many_instance(): void
    {
        $relation = Mockery::mock(HasMany::class);

        $provider = Mockery::mock(Provider::class)->makePartial();
        $provider->shouldReceive('hasMany')
            ->once()
            ->with(ProviderCityMap::class)
            ->andReturn($relation);

        $this->assertInstanceOf(HasMany::class, $provider->cityMaps());
    }
}
