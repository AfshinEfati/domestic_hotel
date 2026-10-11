<?php

namespace App\Modules\HotelProviders\V2\SnappTrip\Support;

use Carbon\CarbonImmutable;
use InvalidArgumentException;

final class SnappTripCalendarWindows
{
    /** Default production calendar request window. Per-hotel learned overrides may reduce it. */
    public const DEFAULT_DAYS = 90;

    /**
     * @return array<int,array{from:string,to:string}>
     */
    public static function split(string $from, string $to, ?int $maxDays = null): array
    {
        $start = CarbonImmutable::parse($from)->startOfDay();
        $end = CarbonImmutable::parse($to)->startOfDay();

        if (!$start->lt($end)) {
            throw new InvalidArgumentException('SnappTrip calendar end date must be after start date.');
        }

        $windowDays = self::normalizeWindowDays($maxDays ?? self::DEFAULT_DAYS);
        $windows = [];
        $cursor = $start;

        while ($cursor->lt($end)) {
            $windowEnd = $cursor->addDays($windowDays);
            if ($windowEnd->gt($end)) {
                $windowEnd = $end;
            }

            $windows[] = [
                'from' => $cursor->toDateString(),
                'to' => $windowEnd->toDateString(),
            ];

            $cursor = $windowEnd;
        }

        return $windows;
    }

    public static function chunkCountForDays(int $days, ?int $maxDays = null): int
    {
        if ($days < 1) {
            throw new InvalidArgumentException('SnappTrip calendar days must be positive.');
        }

        $windowDays = self::normalizeWindowDays($maxDays ?? self::DEFAULT_DAYS);

        return intdiv($days + $windowDays - 1, $windowDays);
    }

    public static function requestCountForDays(int $days, ?int $maxDays = null): int
    {
        // Each window is fetched once for domestic guests and once for foreign guests.
        return self::chunkCountForDays($days, $maxDays) * 2;
    }

    private static function normalizeWindowDays(int $days): int
    {
        if ($days < 1 || $days > self::DEFAULT_DAYS) {
            throw new InvalidArgumentException(
                'SnappTrip calendar window days must be between 1 and '.self::DEFAULT_DAYS.'.'
            );
        }

        return $days;
    }
}
