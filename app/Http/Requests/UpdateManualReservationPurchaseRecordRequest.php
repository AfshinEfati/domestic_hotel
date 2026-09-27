<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateManualReservationPurchaseRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'acc_code' => ['required', 'string', 'max:100'],
            'provider_id' => ['sometimes', 'nullable', 'integer', 'exists:providers,id'],
            'use_hotel_as_provider' => ['sometimes', 'boolean'],
            'purchase_amount' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'confirmation_code' => ['sometimes', 'nullable', 'string', 'max:100'],
            'description' => ['sometimes', 'nullable', 'string'],
            'room_ids' => ['sometimes', 'array'],
            'room_ids.*' => ['integer', 'distinct', 'exists:reservation_rooms,id'],
        ];
    }
}
