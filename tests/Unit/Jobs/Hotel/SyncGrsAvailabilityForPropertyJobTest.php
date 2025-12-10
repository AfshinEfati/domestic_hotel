<?php

namespace Tests\Unit\Jobs\Hotel;

use App\Domain\Hotel\Contracts\ProviderAdapterInterface;
use App\Domain\Hotel\Services\HotelSyncService;
use App\Jobs\Hotel\SyncGrsAvailabilityForPropertyJob;
use App\Models\Provider;
use App\Support\Logging\SystemLogger;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\RateLimiter;
use Mockery;
use Tests\TestCase;

class SyncGrsAvailabilityForPropertyJobTest extends TestCase
{
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

    public function testItReleasesJobWhenRateLimiterDeniesSlot(): void
    {
        $provider = new Provider(['id' => 42]);
        $provider->exists = true;

        $providerMock = Mockery::mock('alias:' . Provider::class);
        $providerMock->shouldReceive('find')->with($provider->id)->andReturn($provider);

        $rateLimiter = Mockery::mock();
        $rateLimiter->shouldReceive('tooManyAttempts')
            ->once()
            ->with('grs-availability', 20)
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
            3,
            0,
            20,
        ])->makePartial();
        $job->shouldReceive('release')->once()->with(3);

        $logger = Mockery::mock(SystemLogger::class);
        $logger->shouldReceive('warning')->never();
        $logger->shouldReceive('error')->never();
        $logger->shouldReceive('info')
            ->once()
            ->with(
                SyncGrsAvailabilityForPropertyJob::class . '::shouldDelayForRateLimit',
                'Delaying GRS availability sync due to provider rate limit',
                Mockery::on(function (array $context) use ($provider) {
                    return $context['provider_id'] === $provider->id
                        && $context['provider_property_id'] === 'PROP-1'
                        && $context['requests_per_minute'] === 20
                        && $context['rate_limit_delay_seconds'] === 3;
                })
            );

        $service = Mockery::mock(HotelSyncService::class);
        $service->shouldReceive('crawlAvailabilityForProperty')->never();

        $adapter = Mockery::mock(ProviderAdapterInterface::class);
        app()->bind(ProviderAdapterInterface::class, function ($app, array $parameters) use ($adapter) {
            return $adapter;
        });

        $job->handle($service, $logger);
    }

    public function testItCallsServiceWhenRateLimiterAllows(): void
    {
        $provider = new Provider(['id' => 84]);
        $provider->exists = true;

        $providerMock = Mockery::mock('alias:' . Provider::class);
        $providerMock->shouldReceive('find')->with($provider->id)->andReturn($provider);

        $rateLimiter = Mockery::mock();
        $rateLimiter->shouldReceive('tooManyAttempts')
            ->once()
            ->with('grs-availability', 20)
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
            3,
            0,
            20,
        ])->makePartial();
        $job->shouldReceive('release')->never();

        $logger = Mockery::mock(SystemLogger::class);
        $logger->shouldReceive('warning')->never();
        $logger->shouldReceive('error')->never();
        $logger->shouldReceive('info')->never();

        $adapter = Mockery::mock(ProviderAdapterInterface::class);
        app()->bind(ProviderAdapterInterface::class, function ($app, array $parameters) use ($adapter) {
            return $adapter;
        });

        $service = Mockery::mock(HotelSyncService::class);
        $service->shouldReceive('crawlAvailabilityForProperty')
            ->once()
            ->withArgs(function ($providerArg, $adapterArg, $propertyKey, $from, $to) use (
                $provider,
                $adapter
            ) {
                return $providerArg === $provider
                    && $adapterArg === $adapter
                    && $propertyKey === 'PROP-2'
                    && $from instanceof CarbonImmutable
                    && $from->toDateString() === '2025-02-01'
                    && $to instanceof CarbonImmutable
                    && $to->toDateString() === '2025-02-10';
            });

        $job->handle($service, $logger);
    }
}
