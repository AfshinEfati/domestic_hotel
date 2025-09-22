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
            'day' => StatusHelper::formatDates($this->day),
            'payload' => $this->payload,
            'created_at' => StatusHelper::formatDates($this->created_at),
            'updated_at' => StatusHelper::formatDates($this->updated_at),
            'room_calendar' => class_exists('App\\Http\\Resources\\RoomCalendarResource')
                ? new \App\Http\Resources\RoomCalendarResource($this->whenLoaded('roomCalendar'))
                : $this->whenLoaded('roomCalendar'),
            'provider' => class_exists('App\\Http\\Resources\\ProviderResource')
                ? new \App\Http\Resources\ProviderResource($this->whenLoaded('provider'))
                : $this->whenLoaded('provider'),
        ];
    }
}
