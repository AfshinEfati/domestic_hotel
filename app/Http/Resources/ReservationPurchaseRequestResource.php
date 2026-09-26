<?php

namespace App\Http\Resources;

use App\Support\Reservation\PurchaseManualReason;
use App\Support\Reservation\PurchaseMethod;
use App\Support\Reservation\ReservationStatus;
use Illuminate\Http\Resources\Json\JsonResource;

class ReservationPurchaseRequestResource extends JsonResource
{
    public function toArray($request): array
    {
        $hotel = $this->hotels->firstWhere('is_final', true);

        return [
            'reservation_id' => $this->id,
            'reservation_number' => $this->reservation_number,
            'status' => (int) $this->status,
            'status_detail' => ReservationStatus::get((int) $this->status),
            'purchases' => $hotel === null
                ? []
                : $hotel->purchases->map(fn ($purchase) => [
                    'id' => $purchase->id,
                    'reservation_hotel_id' => $purchase->reservation_hotel_id,
                    'provider_id' => $purchase->provider_id,
                    'provider_code' => $purchase->provider?->code,
                    'status' => (int) $purchase->status,
                    'status_detail' => ReservationStatus::get((int) $purchase->status),
                    'purchase_mode' => PurchaseMethod::options()[(int) $purchase->purchase_mode] ?? null,
                    'manual_reason' => $purchase->manual_reason === null
                        ? null
                        : PurchaseManualReason::get((int) $purchase->manual_reason),
                    'manual_rule_id' => $purchase->manual_rule_id,
                    'provider_quoted_amount' => $purchase->provider_quoted_amount,
                    'room_ids' => $purchase->segments
                        ->pluck('reservation_room_id')
                        ->map(fn ($id) => (int) $id)
                        ->values(),
                ])->values(),
        ];
    }
}
