<?php

namespace App\Modules\HotelProviders\V2\SnappTrip\Infrastructure\Persistence;

use App\Models\AccommodationProviderMap;
use App\Models\Provider;
use App\Models\ProviderStayPackage;
use App\Models\RoomCalendar;
use App\Models\RoomTypeProviderMap;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class SnappTripAvailabilityRepository
{
    public function __construct(private readonly SnappTripCatalogRepository $catalog)
    {
    }

    /**
     * @param array<int,array<string,mixed>> $rows
     * @param array<int,array<string,mixed>> $packages
     * @return Collection<int,array<string,mixed>> persisted normalized rows for live validation
     */
    public function persist(
        Provider $provider,
        AccommodationProviderMap $map,
        array $rows,
        array $packages,
        bool $foreigner,
    ): Collection {
        if ((int) $map->provider_id !== (int) $provider->id) {
            throw new RuntimeException('SnappTrip availability map belongs to another provider.');
        }

        return DB::transaction(function () use ($provider, $map, $rows, $packages, $foreigner): Collection {
            $normalized = collect();
            $now = now();

            foreach ($rows as $row) {
                if (!is_array($row)) {
                    continue;
                }

                $providerRoomId = trim((string) ($row['provider_room_type_id'] ?? ''));
                $day = trim((string) ($row['day'] ?? ''));
                if ($providerRoomId === '' || $day === '') {
                    continue;
                }

                $maps = $this->catalog->ensureOfferMaps($provider, $map, $providerRoomId, $foreigner);
                $roomMap = $maps['room_map'];
                $rateMap = $maps['rate_plan_map'];

                $payload = [
                    'accommodation_id' => (int) $map->accommodation_id,
                    'room_type_id' => (int) $roomMap->room_type_id,
                    'rate_plan_id' => (int) $rateMap->rate_plan_id,
                    'day' => $day,
                    'rack_rate' => $row['rack_rate'] ?? null,
                    'daily_rate' => $row['daily_rate'] ?? null,
                    'grs_rate' => null,
                    'child_daily_rate' => $row['child_daily_rate'] ?? null,
                    'infant_daily_rate' => $row['infant_daily_rate'] ?? null,
                    'extend_bed_daily_rate' => $row['extend_bed_daily_rate'] ?? null,
                    'min_stay' => $row['min_stay'] ?? null,
                    'max_stay' => $row['max_stay'] ?? null,
                    'cta' => (bool) ($row['cta'] ?? false),
                    'ctd' => (bool) ($row['ctd'] ?? false),
                    'closed' => (bool) ($row['closed'] ?? false),
                    'inventory' => $row['inventory'] ?? null,
                    'provider_id' => (int) $provider->id,
                    'provider_property_id' => (string) $map->provider_property_id,
                    'provider_room_type_id' => $providerRoomId,
                    'provider_rate_plan_id' => (string) $rateMap->provider_rate_plan_id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                RoomCalendar::query()->upsert(
                    [$payload],
                    ['room_type_id', 'rate_plan_id', 'day', 'provider_id'],
                    [
                        'rack_rate',
                        'daily_rate',
                        'grs_rate',
                        'child_daily_rate',
                        'infant_daily_rate',
                        'extend_bed_daily_rate',
                        'min_stay',
                        'max_stay',
                        'cta',
                        'ctd',
                        'closed',
                        'inventory',
                        'provider_property_id',
                        'provider_room_type_id',
                        'provider_rate_plan_id',
                        'updated_at',
                    ],
                );

                $normalized->push([
                    'day' => $day,
                    'inventory' => $row['inventory'] ?? null,
                    'rack_rate' => $row['rack_rate'] ?? null,
                    'daily_rate' => $row['daily_rate'] ?? null,
                    'grs_rate' => null,
                    'child_daily_rate' => $row['child_daily_rate'] ?? null,
                    'infant_daily_rate' => $row['infant_daily_rate'] ?? null,
                    'extend_bed_daily_rate' => $row['extend_bed_daily_rate'] ?? null,
                    'min_stay' => $row['min_stay'] ?? null,
                    'max_stay' => $row['max_stay'] ?? null,
                    'cta' => (bool) ($row['cta'] ?? false),
                    'ctd' => (bool) ($row['ctd'] ?? false),
                    'closed' => (bool) ($row['closed'] ?? false),
                    'room_type_id' => $providerRoomId,
                    'rate_plan_id' => (string) $rateMap->provider_rate_plan_id,
                    'rate_plan_name' => (string) ($rateMap->fa_name ?? ''),
                    'is_foreign_guest' => $foreigner,
                ]);
            }

            // Rack/package restrictions are provider-room stay windows, not a foreign
            // rate-plan dimension. Synchronize them once from the domestic calendar and
            // retain stale rows as inactive history instead of deleting provider data.
            if (!$foreigner) {
                $this->synchronizePackages($provider, $map, $packages, $rows);
            }

            return $normalized->values();
        });
    }

    /**
     * @param array<int,array<string,mixed>> $packages
     * @param array<int,array<string,mixed>> $calendarRows
     */
    private function synchronizePackages(
        Provider $provider,
        AccommodationProviderMap $map,
        array $packages,
        array $calendarRows,
    ): void {
        $days = collect($calendarRows)
            ->filter(fn ($row): bool => is_array($row) && trim((string) ($row['day'] ?? '')) !== '')
            ->pluck('day')
            ->map(fn ($day): string => CarbonImmutable::parse((string) $day)->toDateString())
            ->sort()
            ->values();

        // An empty provider calendar gives us no trustworthy refresh window. Preserve
        // the last known package state rather than deactivating historical/current data
        // based on an ambiguous empty response.
        if ($days->isEmpty()) {
            return;
        }

        $refreshFrom = (string) $days->first();
        $refreshTo = CarbonImmutable::parse((string) $days->last())->addDay()->toDateString();
        $seenAt = now();

        ProviderStayPackage::query()
            ->where('provider_id', $provider->id)
            ->where('accommodation_provider_map_id', $map->id)
            ->where('is_active', true)
            ->where('check_out', '>', $refreshFrom)
            ->where('check_in', '<', $refreshTo)
            ->update(['is_active' => false]);

        foreach ($packages as $package) {
            if (!is_array($package)) {
                continue;
            }

            $providerRoomId = trim((string) ($package['provider_room_type_id'] ?? ''));
            $checkIn = trim((string) ($package['check_in'] ?? ''));
            $checkOut = trim((string) ($package['check_out'] ?? ''));
            if ($providerRoomId === '' || $checkIn === '' || $checkOut === '') {
                continue;
            }

            $roomMap = RoomTypeProviderMap::query()
                ->where('provider_id', $provider->id)
                ->where('accommodation_provider_map_id', $map->id)
                ->where('provider_room_type_id', $providerRoomId)
                ->first();
            if ($roomMap === null) {
                continue;
            }

            $title = isset($package['title']) ? trim((string) $package['title']) : null;
            $key = hash('sha256', implode('|', [$providerRoomId, $checkIn, $checkOut, $title ?? '']));

            ProviderStayPackage::query()->updateOrCreate(
                ['accommodation_provider_map_id' => $map->id, 'package_key' => $key],
                [
                    'provider_id' => $provider->id,
                    'room_type_provider_map_id' => $roomMap->id,
                    'provider_room_type_id' => $providerRoomId,
                    'title' => $title === '' ? null : $title,
                    'check_in' => $checkIn,
                    'check_out' => $checkOut,
                    'is_active' => true,
                    'last_seen_at' => $seenAt,
                ],
            );
        }
    }
}
