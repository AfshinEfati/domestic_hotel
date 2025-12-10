<?php

namespace App\Http\Resources;

use App\Helpers\StatusHelper;
use Illuminate\Http\Resources\Json\JsonResource;

class RoomCalendarSnapshotResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'room_calendar_id' => $this->room_calendar_id,
            'provider_id' => $this->provider_id,
            'payload' => $this->payload,
            'created_at' => StatusHelper::formatDates($this->created_at),
            'updated_at' => StatusHelper::formatDates($this->updated_at),
            'room_calendar' => RoomCalendarResource::make($this->whenLoaded('roomCalendar')),
            'provider' => ProviderResource::make($this->whenLoaded('provider')),
        ];
    }
}
