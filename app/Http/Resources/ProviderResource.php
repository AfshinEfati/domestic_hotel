<?php

namespace App\Http\Resources;

use App\Helpers\StatusHelper;
use App\Support\Provider\ProviderType;
use Illuminate\Http\Resources\Json\JsonResource;

class ProviderResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'fa_name' => $this->fa_name,
            'en_name' => $this->en_name,
            'code' => $this->code,
            'config' => $this->config,
            'is_active' => $this->is_active === null ? null : StatusHelper::getStatus((bool) $this->is_active),
            'is_online' => $this->is_online === null ? null : StatusHelper::getStatus((bool) $this->is_online),
            'provider_type' => $this->provider_type === null
                ? null
                : ProviderType::get((int) $this->provider_type),
            'accommodation_id' => $this->accommodation_id,
            'auth_token' => $this->auth_token,
            'expire_at' => StatusHelper::formatDates($this->expire_at),
            'created_at' => StatusHelper::formatDates($this->created_at),
            'updated_at' => StatusHelper::formatDates($this->updated_at),
            'city_maps' => ProviderCityMapResource::collection($this->whenLoaded('cityMaps')),
        ];
    }
}
