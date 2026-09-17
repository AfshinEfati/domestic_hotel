<?php

namespace App\Http\Requests\Front;

use Illuminate\Foundation\Http\FormRequest;

class AvailableRoomsRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'hotel_id' => [
                'required',
                'integer',
                'exists:accommodations,id',
            ],

            'checkin' => [
                'nullable',
                'required_with:checkout',
                'date_format:Y-m-d',
                'after_or_equal:today',
            ],

            'checkout' => [
                'nullable',
                'required_with:checkin',
                'date_format:Y-m-d',
                'after:checkin',
            ],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
