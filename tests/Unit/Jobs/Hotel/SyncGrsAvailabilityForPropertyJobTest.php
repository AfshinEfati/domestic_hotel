<?php

namespace Tests\Unit\Jobs\Hotel;

use App\Domain\Hotel\Contracts\ProviderAdapterInterface;
use App\Domain\Hotel\Services\HotelSyncService;
use App\Jobs\Hotel\SyncGrsAvailabilityForPropertyJob;
use App\Models\Provider;
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
        Mockery::close();
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

        $service = Mockery::mock(HotelSyncService::class);
        $service->shouldReceive('crawlAvailabilityForProperty')->never();

        $adapter = Mockery::mock(ProviderAdapterInterface::class);
        app()->bind(ProviderAdapterInterface::class, fn () => $adapter);

        $job->handle($service);
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
        app()->bind(ProviderAdapterInterface::class, fn () => $adapter);

        $service = Mockery::mock(HotelSyncService::class);
        $service->shouldReceive('crawlAvailabilityForProperty')
            ->once()
            ->withArgs(function ($providerArg, $adapterArg, $propertyKey, $from, $to) use (
                $provider,
                $adapter
            ) {
                return (int) $providerArg->id === (int) $provider->id
                    && $providerArg->code === $provider->code
                    && $adapterArg === $adapter
                    && $propertyKey === 'PROP-2'
                    && $from instanceof CarbonImmutable
                    && $from->toDateString() === '2025-02-01'
                    && $to instanceof CarbonImmutable
                    && $to->toDateString() === '2025-02-10';
            });

        $job->handle($service);
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

        $service = Mockery::mock(HotelSyncService::class);
        $service->shouldReceive('crawlAvailabilityForProperty')->never();

        $adapter = Mockery::mock(ProviderAdapterInterface::class);
        app()->bind(ProviderAdapterInterface::class, fn () => $adapter);

        $job->handle($service);
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
