<?php

namespace Tests\Unit\Jobs\Hotel;

use App\Domain\Hotel\Contracts\ProviderAdapterInterface;
use App\Domain\Hotel\Repositories\AccommodationRepository;
use App\Domain\Hotel\Repositories\CityRepository;
use App\Domain\Hotel\Repositories\RoomCalendarRepository;
use App\Domain\Hotel\Services\HotelSyncService;
use App\Jobs\Hotel\SyncGrsAvailabilityForPropertyJob;
use App\Models\Provider;
use App\Repositories\Contracts\AccommodationProviderMapRepositoryInterface;
use App\Repositories\Contracts\RatePlanProviderMapRepositoryInterface;
use App\Repositories\Contracts\RoomTypeProviderMapRepositoryInterface;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Mockery;
use Tests\TestCase;

class SyncGrsAvailabilityForPropertyJobTest extends TestCase
{
    use RefreshDatabase;

    private $originalRateLimiter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalRateLimiter = RateLimiter::getFacadeRoot();
    }

    protected function tearDown(): void
    {
        RateLimiter::swap($this->originalRateLimiter);
        parent::tearDown();
    }

    public function testItReleasesJobWhenGlobalGrsRateLimiterDeniesSlot(): void
    {
        $provider = $this->grsProvider(42);

        $rateLimiter = Mockery::mock();
        $rateLimiter->shouldReceive('tooManyAttempts')
            ->once()
            ->with('grs-availability', 10)
            ->andReturn(true);
        $rateLimiter->shouldReceive('availableIn')
            ->once()
            ->with('grs-availability')
            ->andReturn(2);
        $rateLimiter->shouldReceive('hit')->never();
        RateLimiter::swap($rateLimiter);

        $job = Mockery::mock(SyncGrsAvailabilityForPropertyJob::class, [
            $provider->id,
            'PROP-1',
            '2025-01-01',
            '2025-01-03',
            1,
            0,
            10,
            1,
        ])->makePartial();
        $job->shouldReceive('release')->once()->with(2);

        $adapter = Mockery::mock(ProviderAdapterInterface::class);
        $adapter->shouldReceive('fetchAvailability')->never();
        app()->bind(
            ProviderAdapterInterface::class,
            fn ($app, array $params) => $adapter,
        );

        $job->handle($this->service());
    }

    public function testItCallsServiceWhenGlobalGrsRateLimiterAllowsSlot(): void
    {
        $provider = $this->grsProvider(84);

        $rateLimiter = Mockery::mock();
        $rateLimiter->shouldReceive('tooManyAttempts')
            ->once()
            ->with('grs-availability', 10)
            ->andReturn(false);
        $rateLimiter->shouldReceive('availableIn')->never();
        $rateLimiter->shouldReceive('hit')
            ->once()
            ->with('grs-availability', 60);
        RateLimiter::swap($rateLimiter);

        $job = Mockery::mock(SyncGrsAvailabilityForPropertyJob::class, [
            $provider->id,
            'PROP-2',
            '2025-02-01',
            '2025-02-10',
            1,
            0,
            10,
            1,
        ])->makePartial();
        $job->shouldReceive('release')->never();

        $adapter = Mockery::mock(ProviderAdapterInterface::class);
        $adapter->shouldReceive('fetchAvailability')
            ->once()
            ->withArgs(function ($propertyKey, $from, $to): bool {
                return $propertyKey === 'PROP-2'
                    && $from instanceof CarbonImmutable
                    && $from->toDateString() === '2025-02-01'
                    && $to instanceof CarbonImmutable
                    && $to->toDateString() === '2025-02-10';
            })
            ->andReturn(collect());

        app()->bind(
            ProviderAdapterInterface::class,
            fn ($app, array $params) => $adapter,
        );

        $job->handle($this->service());
    }

    public function testItSupportsOneRequestPerTwentyMinutes(): void
    {
        $provider = $this->grsProvider(126);

        $rateLimiter = Mockery::mock();
        $rateLimiter->shouldReceive('tooManyAttempts')
            ->once()
            ->with('grs-availability', 1)
            ->andReturn(true);
        $rateLimiter->shouldReceive('availableIn')
            ->once()
            ->with('grs-availability')
            ->andReturn(0);
        $rateLimiter->shouldReceive('hit')->never();
        RateLimiter::swap($rateLimiter);

        $job = Mockery::mock(SyncGrsAvailabilityForPropertyJob::class, [
            $provider->id,
            'PROP-3',
            '2025-03-01',
            '2025-03-10',
            1,
            0,
            1,
            20,
        ])->makePartial();
        $job->shouldReceive('release')->once()->with(1200);

        $adapter = Mockery::mock(ProviderAdapterInterface::class);
        $adapter->shouldReceive('fetchAvailability')->never();
        app()->bind(
            ProviderAdapterInterface::class,
            fn ($app, array $params) => $adapter,
        );

        $job->handle($this->service());
    }

    private function service(): HotelSyncService
    {
        return new HotelSyncService(
            Mockery::mock(CityRepository::class),
            Mockery::mock(AccommodationRepository::class),
            Mockery::mock(RoomCalendarRepository::class),
            Mockery::mock(AccommodationProviderMapRepositoryInterface::class),
            Mockery::mock(RoomTypeProviderMapRepositoryInterface::class),
            Mockery::mock(RatePlanProviderMapRepositoryInterface::class),
        );
    }

    private function grsProvider(int $id): Provider
    {
        return Provider::query()->create([
            'id' => $id,
            'fa_name' => 'اقامت ۲۴',
            'en_name' => 'GRS',
            'code' => 'grs',
            'config' => [],
            'is_active' => true,
            'is_online' => true,
        ]);
    }
}
