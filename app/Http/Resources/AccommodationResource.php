<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Helpers\StatusHelper;

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
            'city' => class_exists('App\Http\Resources\CityResource')
                ? new CityResource($this->whenLoaded('city'))
                : $this->whenLoaded('city'),
            'type' => class_exists('App\Http\Resources\AccommodationTypeResource')
                ? new \App\Http\Resources\AccommodationTypeResource($this->whenLoaded('type'))
                : $this->whenLoaded('type'),
            'facilities' => class_exists('App\Http\Resources\FacilityResource')
                ? new \App\Http\Resources\FacilityResource($this->whenLoaded('facilities'))
                : $this->whenLoaded('facilities'),
        ];
    }
}
