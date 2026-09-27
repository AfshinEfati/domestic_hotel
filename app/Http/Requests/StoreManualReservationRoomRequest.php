<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreManualReservationRoomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'acc_code' => ['required', 'string', 'max:100'],
            'reservation_hotel_id' => ['sometimes', 'integer', 'exists:reservation_hotels,id'],
            'room_number' => ['required', 'integer', 'min:1'],
            'room_type_id' => ['nullable', 'integer', 'exists:room_types,id'],
            'rate_plan_id' => ['nullable', 'integer', 'exists:rate_plans,id'],
            'room_name' => ['nullable', 'string', 'max:255'],
            'select' => ['sometimes', 'boolean'],
            'guest_ids' => ['sometimes', 'array'],
            'guest_ids.*' => ['integer', 'distinct', 'exists:reservation_guests,id'],
        ];
    }
}
