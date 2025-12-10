<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProviderCityMapRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id' => 'nullable',
            'city_id' => 'required|integer|exists:cities,id',
            'provider_id' => 'required|integer|exists:providers,id',
            'provider_city_id' => 'nullable',
            'fa_name' => 'nullable',
            'en_name' => 'nullable',
            'created_at' => 'nullable|date',
            'updated_at' => 'nullable|date',
        ];
    }
}
