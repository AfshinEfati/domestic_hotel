<?php

namespace App\Modules\HotelProviders\V2\SnappTrip\Infrastructure\Persistence;

use App\Models\AccommodationProviderDetail;
use App\Models\AccommodationProviderMap;
use App\Modules\HotelProviders\V2\SnappTrip\Support\SnappTripCalendarWindows;
use InvalidArgumentException;

final class SnappTripCalendarWindowRepository
{
    public function overrideDays(AccommodationProviderMap $map): ?int
    {
        $value = AccommodationProviderDetail::query()
            ->where('accommodation_provider_map_id', $map->id)
            ->value('calendar_window_days');

        if (!is_numeric($value) || (int) $value < 1) {
            return null;
        }

        return min(SnappTripCalendarWindows::MAX_DAYS, (int) $value);
    }

    public function windowDays(AccommodationProviderMap $map): int
    {
        return $this->overrideDays($map) ?? SnappTripCalendarWindows::MAX_DAYS;
    }

    public function learn(AccommodationProviderMap $map, int $days): int
    {
        $days = $this->normalize($days);
        $current = $this->windowDays($map);
        $effective = min($current, $days);

        if ($effective < $current) {
            AccommodationProviderDetail::query()->updateOrCreate(
                ['accommodation_provider_map_id' => $map->id],
                ['calendar_window_days' => $effective],
            );
        }

        return $effective;
    }

    public function set(AccommodationProviderMap $map, int $days): int
    {
        $days = $this->normalize($days);

        AccommodationProviderDetail::query()->updateOrCreate(
            ['accommodation_provider_map_id' => $map->id],
            ['calendar_window_days' => $days],
        );

        return $days;
    }

    public function clear(AccommodationProviderMap $map): void
    {
        AccommodationProviderDetail::query()
            ->where('accommodation_provider_map_id', $map->id)
            ->update(['calendar_window_days' => null]);
    }

    private function normalize(int $days): int
    {
        if ($days < 1 || $days > SnappTripCalendarWindows::MAX_DAYS) {
            throw new InvalidArgumentException(
                'SnappTrip calendar window days must be between 1 and '.SnappTripCalendarWindows::MAX_DAYS.'.'
            );
        }

        return $days;
    }
}
