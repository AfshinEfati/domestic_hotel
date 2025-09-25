<?php

namespace Tests\Unit\Support\Queue;

use App\Jobs\Hotel\SyncGrsAvailabilityForPropertyJob;
use App\Support\Queue\ResilientCallQueuedHandler;
use Illuminate\Queue\Jobs\SyncJob;
use Illuminate\Support\Str;
use Tests\TestCase;

class ResilientCallQueuedHandlerTest extends TestCase
{
    public function testItRehydratesIncompleteCommands(): void
    {
        $jobInstance = new SyncGrsAvailabilityForPropertyJob(
            providerId: 1,
            providerPropertyId: '3561',
            fromDate: '2025-09-25',
            toDate: '2025-11-24',
            maxAttempts: 3,
            throttleMs: 500
        );

        $serialized = serialize(clone $jobInstance);
        $incomplete = unserialize($serialized, ['allowed_classes' => false]);

        $payload = json_encode([
            'uuid' => (string) Str::uuid(),
            'job' => 'Illuminate\\Queue\\CallQueuedHandler@call',
            'data' => [
                'commandName' => SyncGrsAvailabilityForPropertyJob::class,
                'command' => $serialized,
            ],
        ], JSON_THROW_ON_ERROR);

        $queueJob = new SyncJob(app(), $payload, 'redis', 'default');

        $handler = app(ResilientCallQueuedHandler::class);

        $reflection = new \ReflectionMethod($handler, 'resolveCommand');
        $reflection->setAccessible(true);

        $rehydrated = $reflection->invoke($handler, $queueJob, $incomplete);

        $this->assertInstanceOf(SyncGrsAvailabilityForPropertyJob::class, $rehydrated);
    }

    public function testItRestoresCommandWhenPayloadCommandIsMissing(): void
    {
        $jobInstance = new SyncGrsAvailabilityForPropertyJob(
            providerId: 1,
            providerPropertyId: '3561',
            fromDate: '2025-09-25',
            toDate: '2025-11-24',
            maxAttempts: 3,
            throttleMs: 500
        );

        $serialized = serialize(clone $jobInstance);
        $incomplete = unserialize($serialized, ['allowed_classes' => false]);

        $payload = json_encode([
            'uuid' => (string) Str::uuid(),
            'job' => 'Illuminate\\Queue\\CallQueuedHandler@call',
            'data' => [
                'commandName' => SyncGrsAvailabilityForPropertyJob::class,
                // Intentionally drop the serialized command to exercise the property rehydration path.
            ],
        ], JSON_THROW_ON_ERROR);

        $queueJob = new SyncJob(app(), $payload, 'redis', 'default');

        $handler = app(ResilientCallQueuedHandler::class);

        $reflection = new \ReflectionMethod($handler, 'resolveCommand');
        $reflection->setAccessible(true);

        $rehydrated = $reflection->invoke($handler, $queueJob, $incomplete);

        $this->assertInstanceOf(SyncGrsAvailabilityForPropertyJob::class, $rehydrated);
        $this->assertSame($jobInstance->providerPropertyId, $rehydrated->providerPropertyId);
        $this->assertSame($jobInstance->fromDate, $rehydrated->fromDate);
        $this->assertSame($jobInstance->toDate, $rehydrated->toDate);
    }
}
