<?php

namespace App\DTOs;

use Illuminate\Http\Request;

class ReservationPurchaseSegmentDTO
{
    public function __construct(
        public readonly mixed $reservation_room_id = null,
        public readonly mixed $provider_id = null,
        public readonly mixed $purchase_method = null,
        public readonly mixed $quantity = null,
        public readonly mixed $from_date = null,
        public readonly mixed $to_date = null,
        public readonly mixed $provider_reference = null,
        public readonly mixed $provider_property_id = null,
        public readonly mixed $provider_room_type_id = null,
        public readonly mixed $provider_rate_plan_id = null,
        public readonly mixed $purchased_at = null,
        public readonly mixed $issued_at = null,
        public readonly mixed $notes = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            reservation_room_id: $request->input('reservation_room_id'),
            provider_id: $request->input('provider_id'),
            purchase_method: $request->input('purchase_method'),
            quantity: $request->input('quantity'),
            from_date: $request->input('from_date'),
            to_date: $request->input('to_date'),
            provider_reference: $request->input('provider_reference'),
            provider_property_id: $request->input('provider_property_id'),
            provider_room_type_id: $request->input('provider_room_type_id'),
            provider_rate_plan_id: $request->input('provider_rate_plan_id'),
            purchased_at: $request->input('purchased_at'),
            issued_at: $request->input('issued_at'),
            notes: $request->input('notes'),
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'reservation_room_id' => $this->reservation_room_id,
            'provider_id' => $this->provider_id,
            'purchase_method' => $this->purchase_method,
            'quantity' => $this->quantity,
            'from_date' => $this->from_date,
            'to_date' => $this->to_date,
            'provider_reference' => $this->provider_reference,
            'provider_property_id' => $this->provider_property_id,
            'provider_room_type_id' => $this->provider_room_type_id,
            'provider_rate_plan_id' => $this->provider_rate_plan_id,
            'purchased_at' => $this->purchased_at,
            'issued_at' => $this->issued_at,
            'notes' => $this->notes,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
