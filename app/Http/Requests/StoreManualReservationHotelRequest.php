<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreManualReservationHotelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'acc_code' => ['required', 'string', 'max:100'],
            'accommodation_id' => ['required', 'integer', 'exists:accommodations,id'],
            'select' => ['sometimes', 'boolean'],
        ];
    }
}
