<?php

namespace App\Http\Requests;

use App\Support\Reservation\PaymentSource;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateManualReservationPurchaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'acc_code' => ['required', 'string', 'max:100'],

            'hotel' => ['sometimes', 'array'],
            'hotel.accommodation_id' => ['required_with:hotel', 'integer', 'exists:accommodations,id'],
            'hotel.select' => ['sometimes', 'boolean'],

            'rooms' => ['sometimes', 'array'],
            'rooms.*.reservation_hotel_id' => ['sometimes', 'integer', 'exists:reservation_hotels,id'],
            'rooms.*.room_number' => ['required', 'integer', 'min:1'],
            'rooms.*.room_type_id' => ['nullable', 'integer', 'exists:room_types,id'],
            'rooms.*.rate_plan_id' => ['nullable', 'integer', 'exists:rate_plans,id'],
            'rooms.*.room_name' => ['nullable', 'string', 'max:255'],
            'rooms.*.select' => ['sometimes', 'boolean'],
            'rooms.*.guest_ids' => ['sometimes', 'array'],
            'rooms.*.guest_ids.*' => ['integer', 'distinct', 'exists:reservation_guests,id'],

            'purchases' => ['sometimes', 'array'],
            'purchases.*.id' => ['required', 'integer', 'exists:reservation_purchases,id'],
            'purchases.*.provider_id' => ['nullable', 'integer', 'exists:providers,id'],
            'purchases.*.use_hotel_as_provider' => ['sometimes', 'boolean'],
            'purchases.*.purchase_amount' => ['nullable', 'integer', 'min:1'],
            'purchases.*.confirmation_code' => ['nullable', 'string', 'max:100'],
            'purchases.*.description' => ['nullable', 'string'],
            'purchases.*.room_ids' => ['sometimes', 'array'],
            'purchases.*.room_ids.*' => ['integer', 'distinct', 'exists:reservation_rooms,id'],

            'purchases.*.payments' => ['sometimes', 'array'],
            'purchases.*.payments.*.amount' => ['required', 'integer', 'min:1'],
            'purchases.*.payments.*.source' => [
                'required',
                'integer',
                Rule::in(PaymentSource::manualPurchaseSources()),
            ],
            'purchases.*.payments.*.paid_at' => ['required', 'date'],
        ];
    }
}
