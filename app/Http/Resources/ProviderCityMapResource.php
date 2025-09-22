<?php

namespace App\Http\Resources;

use App\Helpers\StatusHelper;
use Illuminate\Http\Resources\Json\JsonResource;

class ProviderCityMapResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'city_id' => $this->city_id,
            'provider_id' => $this->provider_id,
            'provider_city_id' => $this->provider_city_id,
            'fa_name' => $this->fa_name,
            'en_name' => $this->en_name,
            'created_at' => StatusHelper::formatDates($this->created_at),
            'updated_at' => StatusHelper::formatDates($this->updated_at),
            'city' => class_exists('App\\Http\\Resources\\CityResource')
                ? new \App\Http\Resources\CityResource($this->whenLoaded('city'))
                : $this->whenLoaded('city'),
            'provider' => class_exists('App\\Http\\Resources\\ProviderResource')
                ? new \App\Http\Resources\ProviderResource($this->whenLoaded('provider'))
                : $this->whenLoaded('provider'),
        ];
    }
}
