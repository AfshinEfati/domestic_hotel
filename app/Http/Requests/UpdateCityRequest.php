<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id' => 'sometimes|nullable',
            'country_id' => 'sometimes|nullable|integer|exists:countries,id',
            'state_id' => 'sometimes|nullable|integer|exists:states,id',
            'fa_name' => 'sometimes|nullable',
            'en_name' => 'sometimes|nullable',
            'lat' => 'sometimes|nullable|numeric',
            'lng' => 'sometimes|nullable|numeric',
            'osm_id' => 'sometimes|nullable',
            'is_popular' => 'sometimes|nullable|boolean',
            'is_active' => 'sometimes|nullable|boolean',
            'created_at' => 'sometimes|nullable|date',
            'updated_at' => 'sometimes|nullable|date',
        ];
    }
}
