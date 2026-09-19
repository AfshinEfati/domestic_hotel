<?php

namespace App\Domain\Hotel\Repositories;

use App\Models\RatePlan;
use App\Models\RatePlanProviderMap;
use App\Models\RoomCalendar;
use App\Models\RoomType;
use App\Models\RoomTypeProviderMap;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/** Read-side verification for GRS calendar persistence; all database queries stay here. */
class GrsAvailabilityPersistenceRepository
{
    /**
     * Check the dates actually supplied by GRS, not an assumed complete or bounded calendar.
     * A successful empty response is valid: it contains zero prices to persist.
     *
     * @param Collection<int, array<string, mixed>>|null $response
     */
    public function verifiedRowCount(
        int $providerId,
        int $accommodationId,
        string $providerPropertyId,
        ?Collection $response,
        CarbonInterface $started,
    ): int {
        if ($response === null) {
            throw new RuntimeException('GRS availability response was not received.');
        }
        if ($response->isEmpty()) {
            return 0;
        }

        $roomProviderIds = [];
        $rateProviderIds = [];
        $rows = [];
        foreach ($response as $row) {
            $roomId = trim((string) ($row['room_type_id'] ?? ''));
            $rateId = trim((string) ($row['rate_plan_id'] ?? ''));
            $day = $row['day'] ?? null;
            if ($roomId === '' || $rateId === '' || !is_string($day) || trim($day) === '') {
                Log::warning('GRS availability row skipped: room, rate plan or day missing', [
                    'gds_id' => $accommodationId, 'provider_property_id' => $providerPropertyId,
                ]);
                continue;
            }
            try {
                $normalizedDay = CarbonImmutable::parse($day)->toDateString();
            } catch (Throwable) {
                Log::warning('GRS availability row skipped: invalid date', [
                    'gds_id' => $accommodationId, 'provider_property_id' => $providerPropertyId,
                    'received_day' => $day,
                ]);
                continue;
            }
            // A provider may return dates outside the requested window. Keep the
            // exact normalized dates; HotelSyncService persists these same rows.
            $roomProviderIds[$roomId] = true;
            $rateProviderIds[$rateId] = true;
            $rows[] = [$roomId, $rateId, $normalizedDay];
        }

        if ($rows === []) {
            return 0;
        }

        $roomIds = RoomTypeProviderMap::query()
            ->where('provider_id', $providerId)
            ->whereIn('provider_room_type_id', array_keys($roomProviderIds))
            ->pluck('room_type_id', 'provider_room_type_id')->all();
        $rateIds = RatePlanProviderMap::query()
            ->where('provider_id', $providerId)
            ->whereIn('provider_rate_plan_id', array_keys($rateProviderIds))
            ->pluck('rate_plan_id', 'provider_rate_plan_id')->all();
        if (count($roomIds) !== count($roomProviderIds) || count($rateIds) !== count($rateProviderIds)) {
            throw new RuntimeException('GRS room/rate-plan maps remain incomplete; refresh not marked successful.');
        }

        if (RoomType::query()->where('accommodation_id', $accommodationId)
                ->whereIn('id', array_values($roomIds))->count() !== count($roomIds) ||
            RatePlan::query()->where('accommodation_id', $accommodationId)
                ->whereIn('id', array_values($rateIds))->count() !== count($rateIds)) {
            throw new RuntimeException('GRS room/rate-plan mappings belong to a different accommodation.');
        }

        $expected = [];
        $days = [];
        foreach ($rows as [$roomProviderId, $rateProviderId, $day]) {
            $expected[$roomIds[$roomProviderId].'|'.$rateIds[$rateProviderId].'|'.$day] = true;
            $days[$day] = true;
        }
        $persisted = RoomCalendar::query()
            ->where('provider_id', $providerId)
            ->where('accommodation_id', $accommodationId)
            ->where('provider_property_id', $providerPropertyId)
            ->where('updated_at', '>=', $started)
            ->whereIn('day', array_keys($days))
            ->get(['room_type_id', 'rate_plan_id', 'day']);
        foreach ($persisted as $calendar) {
            unset($expected[$calendar->room_type_id.'|'.$calendar->rate_plan_id.'|'.substr((string) $calendar->day, 0, 10)]);
        }
        if ($expected !== []) {
            throw new RuntimeException('GRS price/stock rows not fully saved: '.count($expected).' calendar dimensions missing.');
        }

        return count($rows);
    }
}
