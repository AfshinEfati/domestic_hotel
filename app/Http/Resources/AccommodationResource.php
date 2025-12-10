<?php

namespace App\Http\Resources;

use App\Helpers\StatusHelper;
use Illuminate\Http\Resources\Json\JsonResource;

class AccommodationResource extends JsonResource
{
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
            'facilities' => FacilityResource::collection($this->whenLoaded('facilities')),
        ];
    }
}
