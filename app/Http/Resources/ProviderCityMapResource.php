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
            'provider_id' => $this->provider_id,
            'city_id' => $this->city_id,
            'provider_city_id' => $this->provider_city_id,
            'city_name' => $this->city_name,
            'created_at' => StatusHelper::formatDates($this->created_at),
            'updated_at' => StatusHelper::formatDates($this->updated_at),
            'city' => CityResource::make($this->whenLoaded('city')),
            'provider' => ProviderResource::make(
                $this->whenLoaded('provider', fn ($provider) => $provider->withoutRelations())
            ),
        ];
    }
}
