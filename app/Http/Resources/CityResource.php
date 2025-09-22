<?php

namespace App\Http\Resources;

use App\Helpers\StatusHelper;
use Illuminate\Http\Resources\Json\JsonResource;

class CityResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'country_id' => $this->country_id,
            'state_id' => $this->state_id,
            'fa_name' => $this->fa_name,
            'en_name' => $this->en_name,
            'lat' => $this->lat,
            'lng' => $this->lng,
            'osm_id' => $this->osm_id,
            'is_popular' => $this->is_popular === null ? null : StatusHelper::getStatus((bool) $this->is_popular),
            'is_active' => $this->is_active === null ? null : StatusHelper::getStatus((bool) $this->is_active),
            'created_at' => StatusHelper::formatDates($this->created_at),
            'updated_at' => StatusHelper::formatDates($this->updated_at),
            'country' => class_exists('App\\Http\\Resources\\CountryResource')
                ? new CountryResource($this->whenLoaded('country'))
                : $this->whenLoaded('country'),
            'state' => class_exists('App\\Http\\Resources\\StateResource')
                ? new StateResource($this->whenLoaded('state'))
                : $this->whenLoaded('state'),
        ];
    }
}
