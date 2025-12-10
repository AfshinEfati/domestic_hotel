<?php

namespace App\Http\Resources;

use App\Helpers\StatusHelper;
use Illuminate\Http\Resources\Json\JsonResource;

class RoomTypeProviderMapResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'room_type_id' => $this->room_type_id,
            'provider_id' => $this->provider_id,
            'provider_room_type_id' => $this->provider_room_type_id,
            'fa_name' => $this->fa_name,
            'en_name' => $this->en_name,
            'created_at' => StatusHelper::formatDates($this->created_at),
            'updated_at' => StatusHelper::formatDates($this->updated_at),
            'room_type' => RoomTypeResource::make($this->whenLoaded('roomType')),
            'provider' => ProviderResource::make(
                $this->whenLoaded('provider', fn ($provider) => $provider->withoutRelations())
            ),
        ];
    }
}
