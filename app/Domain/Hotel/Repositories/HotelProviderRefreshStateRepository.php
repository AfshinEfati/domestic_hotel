<?php

namespace App\Domain\Hotel\Repositories;

use App\Domain\Hotel\Support\ProviderRefreshStateStatus;
use App\Models\AccommodationProviderMap;
use App\Models\HotelProviderRefreshState;
use App\Models\Provider;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class HotelProviderRefreshStateRepository
{
    private const LEASE_SECONDS = 300;

    public function cycleKey(int $scheduleId, int $accommodationId, ?string $sourceDueAt): string
    {
        return hash('sha256', implode('|', [
            $scheduleId,
            $accommodationId,
            $sourceDueAt === null || trim($sourceDueAt) === '' ? 'initial' : trim($sourceDueAt),
        ]));
    }

    public function ensure(
        int $scheduleId,
        int $accommodationId,
        Provider $provider,
        AccommodationProviderMap $map,
        ?string $sourceDueAt,
        bool $dispatchable,
        ?string $terminalOutcome = null,
    ): HotelProviderRefreshState {
        $cycleKey = $this->cycleKey($scheduleId, $accommodationId, $sourceDueAt);
        $state = HotelProviderRefreshState::query()->firstOrCreate(
            [
                'cycle_key' => $cycleKey,
                'provider_id' => (int) $provider->id,
            ],
            [
                'shared_schedule_id' => $scheduleId,
                'accommodation_id' => $accommodationId,
                'accommodation_provider_map_id' => (int) $map->id,
                'source_due_at' => $sourceDueAt,
                'status' => $dispatchable
                    ? ProviderRefreshStateStatus::PENDING
                    : ProviderRefreshStateStatus::ATTEMPTED,
                'outcome' => $dispatchable ? null : $terminalOutcome,
                'completed_at' => $dispatchable ? null : now(),
            ]
        );

        if (in_array((int) $state->status, ProviderRefreshStateStatus::terminal(), true)) {
            return $state;
        }

        $changes = [
            'shared_schedule_id' => $scheduleId,
            'accommodation_id' => $accommodationId,
            'accommodation_provider_map_id' => (int) $map->id,
        ];

        if (!$dispatchable) {
            $changes += [
                'status' => ProviderRefreshStateStatus::ATTEMPTED,
                'outcome' => $terminalOutcome,
                'completed_at' => now(),
                'next_attempt_at' => null,
                'lease_expires_at' => null,
            ];
        }

        $state->forceFill($changes)->save();

        return $state->fresh();
    }

    /** @return Collection<int, HotelProviderRefreshState> */
    public function claimForProvider(int $providerId, int $limit): Collection
    {
        $limit = max(1, $limit);
        $now = CarbonImmutable::now();
        $candidates = HotelProviderRefreshState::query()
            ->where('provider_id', $providerId)
            ->where(function ($query) use ($now): void {
                $query->where(function ($claimable) use ($now): void {
                    $claimable
                        ->whereIn('status', ProviderRefreshStateStatus::claimable())
                        ->where(function ($retryAt) use ($now): void {
                            $retryAt->whereNull('next_attempt_at')
                                ->orWhere('next_attempt_at', '<=', $now);
                        });
                })->orWhere(function ($stale) use ($now): void {
                    $stale
                        ->whereIn('status', [
                            ProviderRefreshStateStatus::QUEUED,
                            ProviderRefreshStateStatus::PROCESSING,
                        ])
                        ->whereNotNull('lease_expires_at')
                        ->where('lease_expires_at', '<=', $now);
                });
            })
            ->orderByRaw('source_due_at IS NOT NULL')
            ->orderBy('source_due_at')
            ->orderBy('id')
            ->limit($limit * 3)
            ->get();

        $claimed = collect();
        foreach ($candidates as $candidate) {
            if ($claimed->count() >= $limit) {
                break;
            }

            $query = HotelProviderRefreshState::query()
                ->whereKey($candidate->id)
                ->where('status', (int) $candidate->status);

            if (in_array((int) $candidate->status, ProviderRefreshStateStatus::claimable(), true)) {
                $query->where(function ($retryAt) use ($now): void {
                    $retryAt->whereNull('next_attempt_at')
                        ->orWhere('next_attempt_at', '<=', $now);
                });
            } else {
                $query->whereNotNull('lease_expires_at')
                    ->where('lease_expires_at', '<=', $now);
            }

            $updated = $query->update([
                'status' => ProviderRefreshStateStatus::QUEUED,
                'queued_at' => $now,
                'lease_expires_at' => $now->addSeconds(self::LEASE_SECONDS),
                'updated_at' => $now,
            ]);

            if ($updated === 1) {
                $claimed->push(HotelProviderRefreshState::query()->findOrFail($candidate->id));
            }
        }

        return $claimed;
    }

    public function start(int $stateId): ?HotelProviderRefreshState
    {
        $now = CarbonImmutable::now();
        $updated = HotelProviderRefreshState::query()
            ->whereKey($stateId)
            ->where('status', ProviderRefreshStateStatus::QUEUED)
            ->update([
                'status' => ProviderRefreshStateStatus::PROCESSING,
                'attempts' => DB::raw('attempts + 1'),
                'started_at' => $now,
                'last_attempt_at' => $now,
                'lease_expires_at' => $now->addSeconds(self::LEASE_SECONDS),
                'updated_at' => $now,
            ]);

        return $updated === 1
            ? HotelProviderRefreshState::query()->find($stateId)
            : null;
    }

    public function markDone(int $stateId, string $outcome): ?HotelProviderRefreshState
    {
        $now = CarbonImmutable::now();
        HotelProviderRefreshState::query()->whereKey($stateId)->update([
            'status' => ProviderRefreshStateStatus::DONE,
            'outcome' => $outcome,
            'last_success_at' => $now,
            'completed_at' => $now,
            'next_attempt_at' => null,
            'lease_expires_at' => null,
            'updated_at' => $now,
        ]);

        return HotelProviderRefreshState::query()->find($stateId);
    }

    public function markAttempted(int $stateId, string $outcome): ?HotelProviderRefreshState
    {
        $now = CarbonImmutable::now();
        HotelProviderRefreshState::query()->whereKey($stateId)->update([
            'status' => ProviderRefreshStateStatus::ATTEMPTED,
            'outcome' => $outcome,
            'completed_at' => $now,
            'next_attempt_at' => null,
            'lease_expires_at' => null,
            'updated_at' => $now,
        ]);

        return HotelProviderRefreshState::query()->find($stateId);
    }

    public function markRetry(int $stateId, string $outcome, int $delaySeconds): ?HotelProviderRefreshState
    {
        $now = CarbonImmutable::now();
        HotelProviderRefreshState::query()->whereKey($stateId)->update([
            'status' => ProviderRefreshStateStatus::RETRY,
            'outcome' => $outcome,
            'next_attempt_at' => $now->addSeconds(max(1, $delaySeconds)),
            'completed_at' => null,
            'lease_expires_at' => null,
            'updated_at' => $now,
        ]);

        return HotelProviderRefreshState::query()->find($stateId);
    }

    public function hasOpenState(string $cycleKey): bool
    {
        return HotelProviderRefreshState::query()
            ->where('cycle_key', $cycleKey)
            ->whereNotIn('status', ProviderRefreshStateStatus::terminal())
            ->exists();
    }

    public function countForCycle(string $cycleKey): int
    {
        return HotelProviderRefreshState::query()
            ->where('cycle_key', $cycleKey)
            ->count();
    }

    public function find(int $stateId): ?HotelProviderRefreshState
    {
        return HotelProviderRefreshState::query()->find($stateId);
    }
}
