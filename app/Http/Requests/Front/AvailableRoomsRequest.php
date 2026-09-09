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
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
