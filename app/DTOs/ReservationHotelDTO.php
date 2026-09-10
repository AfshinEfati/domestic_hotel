<?php

namespace App\DTOs;

use Illuminate\Http\Request;

class ReservationHotelDTO
{
    public function __construct(
        public readonly mixed $reservation_id = null,
        public readonly mixed $accommodation_id = null,
        public readonly mixed $type = null,
        public readonly mixed $is_final = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            reservation_id: $request->input('reservation_id'),
            accommodation_id: $request->input('accommodation_id'),
            type: $request->input('type'),
            is_final: $request->input('is_final'),
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'reservation_id' => $this->reservation_id,
            'accommodation_id' => $this->accommodation_id,
            'type' => $this->type,
            'is_final' => $this->is_final,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
