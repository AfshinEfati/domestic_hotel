<?php

namespace App\Modules\HotelProviders\V2\SnappTrip\Infrastructure\Persistence;

use App\Models\AccommodationProviderDetail;
use App\Models\AccommodationProviderMap;
use InvalidArgumentException;

final class SnappTripRefreshHorizonRepository
{
    public function overrideDays(AccommodationProviderMap $map): ?int
    {
        $value = AccommodationProviderDetail::query()
            ->where('accommodation_provider_map_id', $map->id)
            ->value('refresh_horizon_days');

        return is_numeric($value) && (int) $value > 0 ? (int) $value : null;
    }

    public function learn(AccommodationProviderMap $map, int $days): int
    {
        $days = $this->normalize($days);
        $current = $this->overrideDays($map);
        $effective = $current === null ? $days : min($current, $days);

        if ($current === null || $effective < $current) {
            AccommodationProviderDetail::query()->updateOrCreate(
                ['accommodation_provider_map_id' => $map->id],
                ['refresh_horizon_days' => $effective],
            );
        }

        return $effective;
    }

    public function set(AccommodationProviderMap $map, int $days): int
    {
        $days = $this->normalize($days);

        AccommodationProviderDetail::query()->updateOrCreate(
            ['accommodation_provider_map_id' => $map->id],
            ['refresh_horizon_days' => $days],
        );

        return $days;
    }

    public function clear(AccommodationProviderMap $map): void
    {
        AccommodationProviderDetail::query()
            ->where('accommodation_provider_map_id', $map->id)
            ->update(['refresh_horizon_days' => null]);
    }

    private function normalize(int $days): int
    {
        if ($days < 1 || $days > 3650) {
            throw new InvalidArgumentException('SnappTrip refresh horizon days must be between 1 and 3650.');
        }

        return $days;
    }
}
