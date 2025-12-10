<?php

namespace App\DTOs;

use Illuminate\Http\Request;

class RoomCalendarSnapshotDTO
{
    public mixed $id;
    public mixed $room_calendar_id;
    public mixed $provider_id;
    public mixed $day;
    public mixed $payload;
    public mixed $created_at;
    public mixed $updated_at;

    public function __construct(
        mixed $id = null,
        mixed $room_calendar_id = null,
        mixed $provider_id = null,
        mixed $day = null,
        mixed $payload = null,
        mixed $created_at = null,
        mixed $updated_at = null
    ) {
        $this->id = $id;
        $this->room_calendar_id = $room_calendar_id;
        $this->provider_id = $provider_id;
        $this->day = $day;
        $this->payload = $payload;
        $this->created_at = $created_at;
        $this->updated_at = $updated_at;
    }

    public static function fromRequest(Request $request): self
    {
        $dto = new self();
        $dto->id = $request->input('id');
        $dto->room_calendar_id = $request->input('room_calendar_id');
        $dto->provider_id = $request->input('provider_id');
        $dto->day = $request->input('day');
        $dto->payload = $request->input('payload');
        $dto->created_at = $request->input('created_at');
        $dto->updated_at = $request->input('updated_at');

        return $dto;
    }

    public function toArray(): array
    {
        $out = [];
        if ($this->id !== null) { $out['id'] = $this->id; }
        if ($this->room_calendar_id !== null) { $out['room_calendar_id'] = $this->room_calendar_id; }
        if ($this->provider_id !== null) { $out['provider_id'] = $this->provider_id; }
        if ($this->day !== null) { $out['day'] = $this->day; }
        if ($this->payload !== null) { $out['payload'] = $this->payload; }
        if ($this->created_at !== null) { $out['created_at'] = $this->created_at; }
        if ($this->updated_at !== null) { $out['updated_at'] = $this->updated_at; }

        return $out;
    }
}
