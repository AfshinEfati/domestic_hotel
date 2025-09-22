<?php

namespace App\Http\Resources;

use App\Helpers\StatusHelper;
use Illuminate\Http\Resources\Json\JsonResource;

class AccommodationProviderMapResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'accommodation_id' => $this->accommodation_id,
            'provider_id' => $this->provider_id,
            'provider_property_id' => $this->provider_property_id,
            'fa_name' => $this->fa_name,
            'en_name' => $this->en_name,
            'created_at' => StatusHelper::formatDates($this->created_at),
            'updated_at' => StatusHelper::formatDates($this->updated_at),
            'accommodation' => class_exists('App\\Http\\Resources\\AccommodationResource')
                ? new \App\Http\Resources\AccommodationResource($this->whenLoaded('accommodation'))
                : $this->whenLoaded('accommodation'),
            'provider' => class_exists('App\\Http\\Resources\\ProviderResource')
                ? new \App\Http\Resources\ProviderResource($this->whenLoaded('provider'))
                : $this->whenLoaded('provider'),
        ];
    }
}
