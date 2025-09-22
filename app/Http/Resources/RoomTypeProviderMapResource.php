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
            'room_type' => class_exists('App\\Http\\Resources\\RoomTypeResource')
                ? new \App\Http\Resources\RoomTypeResource($this->whenLoaded('roomType'))
                : $this->whenLoaded('roomType'),
            'provider' => class_exists('App\\Http\\Resources\\ProviderResource')
                ? new \App\Http\Resources\ProviderResource($this->whenLoaded('provider'))
                : $this->whenLoaded('provider'),
        ];
    }
}
