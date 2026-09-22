<?php

namespace App\Http\Requests\Front;

use Illuminate\Foundation\Http\FormRequest;

class AccommodationListRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'from'=>['nullable','integer'],
            'to'=>['nullable','integer'],
            'page' => [
                'nullable',
                'integer',
                'min:1',
            ],

            'per_page' => [
                'nullable',
                'integer',
                'min:1',
                'max:100',
            ],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
