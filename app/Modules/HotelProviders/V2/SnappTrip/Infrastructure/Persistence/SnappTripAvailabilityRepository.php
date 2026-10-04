<?php

namespace App\Modules\HotelProviders\V2\SnappTrip\Infrastructure\Persistence;

use App\Models\AccommodationProviderMap;
use App\Models\Provider;
use App\Models\ProviderStayPackage;
use App\Models\RoomCalendar;
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
     * @return Collection<int,array<string,mixed>> persisted normalized rows for adapter/live validation
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

            $this->persistPackages($provider, $map, $packages);

            return $normalized->values();
        });
    }

    /** @param array<int,array<string,mixed>> $packages */
    public function persistPackages(Provider $provider, AccommodationProviderMap $map, array $packages): void
    {
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

            $roomMap = \App\Models\RoomTypeProviderMap::query()
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
                ],
            );
        }
    }

    public function packagesAllowStay(
        int $providerId,
        int $accommodationId,
        int $roomTypeId,
        string $checkIn,
        string $checkOut,
    ): bool {
        $base = ProviderStayPackage::query()
            ->where('provider_id', $providerId)
            ->whereHas('accommodationProviderMap', fn ($query) => $query->where('accommodation_id', $accommodationId))
            ->whereHas('roomTypeProviderMap', fn ($query) => $query->where('room_type_id', $roomTypeId));

        if (!(clone $base)->exists()) {
            return true;
        }

        $packages = (clone $base)
            ->where('check_out', '>', $checkIn)
            ->where('check_in', '<', $checkOut)
            ->orderBy('check_in')
            ->orderBy('check_out')
            ->get(['check_in', 'check_out']);

        if ($packages->isEmpty()) {
            return false;
        }

        $cursor = $checkIn;
        foreach ($packages as $package) {
            $from = $package->check_in->toDateString();
            $to = $package->check_out->toDateString();
            if ($from > $cursor) {
                continue;
            }
            if ($to > $cursor) {
                $cursor = $to;
            }
            if ($cursor >= $checkOut) {
                return true;
            }
        }

        return false;
    }
}
