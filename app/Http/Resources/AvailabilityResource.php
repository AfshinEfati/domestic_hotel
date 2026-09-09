<?php

namespace App\Http\Resources;

use App\Models\Accommodation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Accommodation */
class AvailabilityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'fa_name' => $this->fa_name,
            'en_name' => $this->en_name,
            'star' => $this->star,
            'grade' => $this->grade,
            'address' => $this->address,
            'lat' => $this->lat,
            'lng' => $this->lng,
            'city' => CityResource::make($this->whenLoaded('city')),
            'type' => AccommodationTypeResource::make($this->whenLoaded('type')),
            'available_rooms' => $this->formatAvailableRooms($this->available_rooms ?? []),
            'childPolicy' => HotelChildPolicyResource::make($this->whenLoaded('childPolicy')),
            'facilities' => FacilityResource::collection($this->whenLoaded('facilities')),
        ];
    }

    /**
     * فرمت‌بندی اتاق‌های موجود با اطلاعات قیمت‌گذاری
     */
    private function formatAvailableRooms($rooms)
    {
        return collect($rooms)->map(function ($room) {
            return [
                'id' => $room->id,
                'fa_name' => $room->fa_name,
                'en_name' => $room->en_name,
                'capacity' => $room->capacity,
                'extra_capacity' => $room->extra_capacity,
                'single_bed_count' => $room->single_bed_count,
                'double_bed_count' => $room->double_bed_count,
                'sofa_bed_count' => $room->sofa_bed_count,
                'out_of_service' => $room->out_of_service,
                'pricing' => $room->pricing ?? [],
                'total_price' => $room->total_price ?? 0,
                'nightly_prices' => $room->nightly_prices ?? [],
                'roomTypeName' => $room->roomTypeName ? new RoomTypeNameResource($room->roomTypeName) : null,
                'ratePlan'=> $room->ratePlan ? new RatePlanResource($room->ratePlan) : null,
            ];
        })->values();
    }
}
