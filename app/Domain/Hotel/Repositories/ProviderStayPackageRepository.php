<?php

namespace App\Domain\Hotel\Repositories;

use App\Models\ProviderStayPackage;

final class ProviderStayPackageRepository
{
    /**
     * A provider without active package rows has no package restriction. When active
     * packages exist, the selected stay must be continuously covered by one or more
     * adjacent/overlapping package windows without a gap. Historical inactive package
     * rows never constrain a new sale.
     */
    public function allowsStay(
        int $providerId,
        int $accommodationId,
        int $roomTypeId,
        string $checkIn,
        string $checkOut,
    ): bool {
        $base = ProviderStayPackage::query()
            ->where('provider_id', $providerId)
            ->where('is_active', true)
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
