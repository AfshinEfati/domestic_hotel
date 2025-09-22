<?php

namespace App\Http\Resources;

use App\Helpers\StatusHelper;
use Illuminate\Http\Resources\Json\JsonResource;

class RoomCalendarResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'room_type_id' => $this->room_type_id,
            'rate_plan_id' => $this->rate_plan_id,
            'provider_id' => $this->provider_id,
            'board' => $this->board,
            'date' => $this->date,
            'price' => $this->price,
            'allotment' => $this->allotment,
            'sold' => $this->sold,
            'release' => $this->release,
            'minimum_stay' => $this->minimum_stay,
            'created_at' => StatusHelper::formatDates($this->created_at),
            'updated_at' => StatusHelper::formatDates($this->updated_at),
            'accommodation' => AccommodationResource::make($this->whenLoaded('accommodation')),
            'room_type' => RoomTypeResource::make($this->whenLoaded('roomType')),
            'rate_plan' => RatePlanResource::make($this->whenLoaded('ratePlan')),
            'provider' => ProviderResource::make($this->whenLoaded('provider')),
        ];
    }
}
