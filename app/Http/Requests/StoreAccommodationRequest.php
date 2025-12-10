<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAccommodationRequest extends FormRequest
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
            'fa_name' => 'nullable',
            'en_name' => 'nullable',
            'accommodation_type_id' => 'required|integer|exists:accommodation_types,id',
            'star' => 'nullable',
            'grade' => 'nullable',
            'address' => 'nullable',
            'lat' => 'nullable',
            'lng' => 'nullable',
            'is_active' => 'nullable',
            'created_at' => 'nullable|date',
            'updated_at' => 'nullable|date',
        ];
    }
}
