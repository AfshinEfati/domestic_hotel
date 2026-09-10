<?php

namespace App\DTOs;

use Illuminate\Http\Request;

class ReservationRoomDTO
{
    public function __construct(
        public readonly mixed $reservation_hotel_id = null,
        public readonly mixed $room_type_id = null,
        public readonly mixed $rate_plan_id = null,
        public readonly mixed $room_name = null,
        public readonly mixed $quantity = null,
        public readonly mixed $check_in = null,
        public readonly mixed $check_out = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            reservation_hotel_id: $request->input('reservation_hotel_id'),
            room_type_id: $request->input('room_type_id'),
            rate_plan_id: $request->input('rate_plan_id'),
            room_name: $request->input('room_name'),
            quantity: $request->input('quantity'),
            check_in: $request->input('check_in'),
            check_out: $request->input('check_out'),
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'reservation_hotel_id' => $this->reservation_hotel_id,
            'room_type_id' => $this->room_type_id,
            'rate_plan_id' => $this->rate_plan_id,
            'room_name' => $this->room_name,
            'quantity' => $this->quantity,
            'check_in' => $this->check_in,
            'check_out' => $this->check_out,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
