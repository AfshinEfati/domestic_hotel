<?php

namespace App\Http\Resources;

use App\Helpers\StatusHelper;
use App\Support\Reservation\ReservationStatus;
use Illuminate\Http\Resources\Json\JsonResource;

class ReservationResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'agency_id' => $this->agency_id,
            'status' => (int) $this->status,
            'status_detail' => ReservationStatus::get((int) $this->status),
            'check_in' => $this->check_in?->format('Y-m-d'),
            'check_out' => $this->check_out?->format('Y-m-d'),
            'price' => $this->sale_amount,
            'price_change' => $this->initial_sale_amount !== null
                && $this->validated_sale_amount !== null
                && $this->validated_sale_amount > $this->initial_sale_amount,
            'initial_price' => $this->initial_sale_amount,
            'validated_price' => $this->validated_sale_amount,
            'validation_error' => $this->validation_error,
            'sale_amount' => $this->sale_amount,
            'tax_amount' => $this->tax_amount,
            'commission_amount' => $this->commission_amount,
            'booker' => [
                'first_name' => $this->booker_first_name,
                'last_name' => $this->booker_last_name,
                'mobile' => $this->booker_mobile,
                'email' => $this->booker_email,
            ],
            'acc_code' => $this->acc_code,
            'hotels' => $this->whenLoaded('hotels', function () {
                return $this->hotels->map(function ($hotel) {
                    return [
                        'id' => $hotel->id,
                        'accommodation_id' => $hotel->accommodation_id,
                        'type' => $hotel->type,
                        'is_final' => (bool) $hotel->is_final,
                        'rooms' => $hotel->relationLoaded('rooms')
                            ? $hotel->rooms->map(function ($room) {
                                return [
                                    'id' => $room->id,
                                    'room_number' => $room->room_number,
                                    'type' => $room->type,
                                    'is_final' => (bool) $room->is_final,
                                    'room_calendar_id' => $room->room_calendar_id,
                                    'room_type_id' => $room->room_type_id,
                                    'rate_plan_id' => $room->rate_plan_id,
                                    'room_name' => $room->room_name,
                                    'initial_price' => $room->initial_price,
                                    'validated_price' => $room->validated_price,
                                    'guests' => $room->relationLoaded('guests')
                                        ? $room->guests->map(fn ($guest) => [
                                            'id' => $guest->id,
                                            'type' => $guest->type,
                                            'first_name' => $guest->first_name,
                                            'last_name' => $guest->last_name,
                                            'gender' => $guest->gender,
                                            'birth_date' => $guest->birth_date?->format('Y-m-d'),
                                            'country_id' => $guest->country_id,
                                            'national_id' => $guest->national_id,
                                            'passport_number' => $guest->passport_number,
                                            'passport_issuer_country_id' => $guest->passport_issuer_country_id,
                                            'passport_expiry_date' => $guest->passport_expiry_date?->format('Y-m-d'),
                                        ])->values()
                                        : [],
                                ];
                            })->values()
                            : [],
                    ];
                })->values();
            }),
            'created_at' => StatusHelper::formatDates($this->created_at),
            'updated_at' => StatusHelper::formatDates($this->updated_at),
        ];
    }
}
