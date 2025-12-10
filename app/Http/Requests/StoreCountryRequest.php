<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCountryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id' => 'nullable',
            'fa_name' => 'nullable',
            'en_name' => 'nullable',
            'iso2' => 'nullable|string|size:2',
            'iso3' => 'nullable|string|size:3',
            'created_at' => 'nullable|date',
            'updated_at' => 'nullable|date',
        ];
    }
}
