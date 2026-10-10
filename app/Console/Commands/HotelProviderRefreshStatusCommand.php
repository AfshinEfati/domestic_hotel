<?php

namespace App\Console\Commands;

use App\Domain\Hotel\Support\ProviderRefreshStateStatus;
use App\Models\AccommodationProviderMap;
use App\Models\HotelPriceRefreshSchedule;
use App\Models\HotelProviderRefreshState;
use App\Models\Provider;
use Illuminate\Console\Command;

class HotelProviderRefreshStatusCommand extends Command
{
    protected $signature = 'hotel:provider-refresh-status {--provider= : Optional provider code} {--limit=20 : Recent local states to show}';

    protected $description = 'Show shared due hotels and local per-provider refresh state without using Tinker.';

    public function handle(): int
    {
        $providerCode = trim((string) $this->option('provider'));
        $provider = null;
        if ($providerCode !== '') {
            $provider = Provider::query()->where('code', $providerCode)->first();
            if ($provider === null) {
                $this->error("Provider [{$providerCode}] was not found.");
                return self::FAILURE;
            }
        }

        $dueRows = HotelPriceRefreshSchedule::query()
            ->where('is_active', true)
            ->whereNotNull('gds_id')
            ->where(function ($query): void {
                $query->whereNull('next_gds_run_at')
                    ->orWhereRaw('next_gds_run_at <= CURRENT_TIMESTAMP');
            })
            ->get(['gds_id']);

        $dueIds = $dueRows
            ->pluck('gds_id')
            ->map(fn ($id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values();

        $this->info('Shared due rows: '.$dueRows->count());
        $this->info('Shared due unique hotels: '.$dueIds->count());
        $this->info('Shared duplicate due rows: '.max(0, $dueRows->count() - $dueIds->count()));

        $providers = $provider !== null
            ? collect([$provider])
            : Provider::query()->orderBy('id')->get();

        $providerSummary = $providers->map(function (Provider $item) use ($dueIds): array {
            $maps = AccommodationProviderMap::query()->where('provider_id', (int) $item->id);
            $totalMaps = (clone $maps)->count();
            $uniqueMappedHotels = (clone $maps)->distinct()->count('accommodation_id');
            $usableMaps = (clone $maps)
                ->where('is_disabled', false)
                ->whereNotNull('provider_property_id')
                ->where('provider_property_id', '<>', '')
                ->count();
            $dueUsableHotels = $dueIds->isEmpty()
                ? 0
                : (clone $maps)
                    ->where('is_disabled', false)
                    ->whereNotNull('provider_property_id')
                    ->where('provider_property_id', '<>', '')
                    ->whereIn('accommodation_id', $dueIds->all())
                    ->distinct()
                    ->count('accommodation_id');

            return [
                (int) $item->id,
                (string) $item->code,
                $item->is_active ? 'yes' : 'no',
                $totalMaps,
                $uniqueMappedHotels,
                max(0, $totalMaps - $uniqueMappedHotels),
                $usableMaps,
                $dueUsableHotels,
                HotelProviderRefreshState::query()->where('provider_id', (int) $item->id)->count(),
            ];
        })->all();

        $this->table(
            ['provider id', 'code', 'active', 'maps', 'mapped hotels', 'duplicate maps', 'usable maps', 'due usable hotels', 'local states'],
            $providerSummary,
        );

        $duplicateQuery = AccommodationProviderMap::query()
            ->select('provider_id', 'accommodation_id')
            ->selectRaw('COUNT(*) AS aggregate')
            ->groupBy('provider_id', 'accommodation_id')
            ->havingRaw('COUNT(*) > 1')
            ->orderBy('provider_id')
            ->orderBy('accommodation_id');

        if ($provider !== null) {
            $duplicateQuery->where('provider_id', (int) $provider->id);
        }

        $duplicateGroups = $duplicateQuery->limit(50)->get();
        if ($duplicateGroups->isNotEmpty()) {
            $providerCodes = Provider::query()
                ->whereIn('id', $duplicateGroups->pluck('provider_id')->unique()->all())
                ->pluck('code', 'id');

            $this->warn('Duplicate accommodation/provider map groups detected:');
            $this->table(
                ['provider', 'hotel', 'map ids', 'provider property ids', 'disabled'],
                $duplicateGroups->map(function ($group) use ($providerCodes): array {
                    $maps = AccommodationProviderMap::query()
                        ->where('provider_id', (int) $group->provider_id)
                        ->where('accommodation_id', (int) $group->accommodation_id)
                        ->orderBy('id')
                        ->get(['id', 'provider_property_id', 'is_disabled']);

                    return [
                        (string) ($providerCodes[(int) $group->provider_id] ?? $group->provider_id),
                        (int) $group->accommodation_id,
                        $maps->pluck('id')->implode(','),
                        $maps->map(fn (AccommodationProviderMap $map): string =>
                            trim((string) $map->provider_property_id) !== ''
                                ? (string) $map->provider_property_id
                                : '-'
                        )->implode(','),
                        $maps->map(fn (AccommodationProviderMap $map): string =>
                            $map->is_disabled ? 'yes' : 'no'
                        )->implode(','),
                    ];
                })->all(),
            );
        }

        $base = HotelProviderRefreshState::query();
        if ($provider !== null) {
            $base->where('provider_id', (int) $provider->id);
        }

        $counts = (clone $base)
            ->selectRaw('status, COUNT(*) AS aggregate')
            ->groupBy('status')
            ->orderBy('status')
            ->get()
            ->mapWithKeys(fn ($row): array => [
                ProviderRefreshStateStatus::name((int) $row->status) => (int) $row->aggregate,
            ])
            ->all();

        $this->table(
            ['state', 'count'],
            collect($counts)->map(fn (int $count, string $state): array => [$state, $count])->values()->all(),
        );

        $limit = max(1, min(200, (int) $this->option('limit')));
        $recent = (clone $base)
            ->with('provider:id,code')
            ->latest('id')
            ->limit($limit)
            ->get();

        $this->table(
            ['id', 'hotel', 'provider', 'state', 'outcome', 'attempts', 'next attempt', 'completed'],
            $recent->map(fn (HotelProviderRefreshState $state): array => [
                (int) $state->id,
                (int) $state->accommodation_id,
                (string) ($state->provider?->code ?? $state->provider_id),
                ProviderRefreshStateStatus::name((int) $state->status),
                (string) ($state->outcome ?? '-'),
                (int) $state->attempts,
                $state->next_attempt_at?->format('Y-m-d H:i:s') ?? '-',
                $state->completed_at?->format('Y-m-d H:i:s') ?? '-',
            ])->all(),
        );

        return self::SUCCESS;
    }
}
