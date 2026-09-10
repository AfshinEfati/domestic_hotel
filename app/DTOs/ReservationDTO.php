<?php

namespace App\DTOs;

use Illuminate\Http\Request;

class ReservationDTO
{
    public function __construct(
        public readonly mixed $reservation_number = null,
        public readonly mixed $status = null,
        public readonly mixed $total_price = null,
        public readonly mixed $email = null,
        public readonly mixed $mobile = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            reservation_number: $request->input('reservation_number'),
            status: $request->input('status'),
            total_price: $request->input('total_price'),
            email: $request->input('email'),
            mobile: $request->input('mobile'),
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'reservation_number' => $this->reservation_number,
            'status' => $this->status,
            'total_price' => $this->total_price,
            'email' => $this->email,
            'mobile' => $this->mobile,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
