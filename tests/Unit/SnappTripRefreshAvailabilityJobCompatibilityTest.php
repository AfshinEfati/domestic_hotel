<?php

namespace Tests\Unit;

use App\Domain\Hotel\Services\ProviderRefreshCoordinator;
use App\Modules\HotelProviders\V2\Shared\ProviderOutboundGuard;
use App\Modules\HotelProviders\V2\SnappTrip\Application\RefreshScheduledAvailability;
use App\Modules\HotelProviders\V2\SnappTrip\Jobs\RefreshAvailabilityJob;
use App\Repositories\Contracts\AccommodationProviderMapRepositoryInterface;
use App\Repositories\Contracts\ProviderRepositoryInterface;
use App\Services\Alerts\TelegramAlertService;
use ReflectionClass;
use Tests\TestCase;

class SnappTripRefreshAvailabilityJobCompatibilityTest extends TestCase
{
    public function test_legacy_payload_without_coordinator_properties_does_not_crash(): void
    {
        $job = new RefreshAvailabilityJob(10, 2076, 4, 90);

        // Reproduce a job payload serialized before refreshStateId/refreshCycleKey
        // existed on the class: typed properties are absent/uninitialized.
        unset($job->refreshStateId, $job->refreshCycleKey);

        $this->assertSame('snapptrip-v2-refresh:2076', $job->uniqueId());

        $providers = $this->createMock(ProviderRepositoryInterface::class);
        $providers->expects($this->once())
            ->method('findByCode')
            ->with('snap')
            ->willReturn(null);

        $guard = new ProviderOutboundGuard($providers);
        $refresh = (new ReflectionClass(RefreshScheduledAvailability::class))->newInstanceWithoutConstructor();
        $maps = $this->createMock(AccommodationProviderMapRepositoryInterface::class);
        $coordinator = (new ReflectionClass(ProviderRefreshCoordinator::class))->newInstanceWithoutConstructor();
        $alerts = (new ReflectionClass(TelegramAlertService::class))->newInstanceWithoutConstructor();

        $job->handle($guard, $refresh, $maps, $coordinator, $alerts);

        $this->addToAssertionCount(1);
    }
}
