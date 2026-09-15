<?php

namespace App\Http\Resources;

use App\Helpers\StatusHelper;
use App\Models\Accommodation;
use Illuminate\Http\Resources\Json\JsonResource;

class AccommodationResource extends JsonResource
{
    /** @mixin Accommodation */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'city_id' => $this->city_id,
            'fa_name' => $this->fa_name,
            'en_name' => $this->en_name,
            'accommodation_type_id' => $this->accommodation_type_id,
            'star' => $this->star,
            'grade' => $this->grade,
            'address' => $this->address,
            'lat' => $this->lat,
            'lng' => $this->lng,
            'is_active' => StatusHelper::getStatus((bool) $this->is_active),
            'created_at' => StatusHelper::formatDates($this->created_at),
            'updated_at' => StatusHelper::formatDates($this->updated_at),
            'city' => CityResource::make($this->whenLoaded('city')),
            'type' => AccommodationTypeResource::make(
                $this->whenLoaded('type', fn ($type) => $type->withoutRelations())
            ),
            'rate_plan' => RatePlanResource::make($this->rate_plan),
            'rules' => RuleResource::collection($this->whenLoaded('rules')),
            'childPolicy'=> HotelChildPolicyResource::make($this->whenLoaded('childPolicy')),
            'rooms' => RoomTypeResource::collection($this->whenLoaded('rooms')),
            'facilities' => FacilityResource::collection($this->whenLoaded('facilities')),
        ];
    }
}
