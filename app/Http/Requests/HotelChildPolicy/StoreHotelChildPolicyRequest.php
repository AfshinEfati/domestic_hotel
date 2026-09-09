<?php

namespace App\Http\Requests\HotelChildPolicy;

use Illuminate\Foundation\Http\FormRequest;

class StoreHotelChildPolicyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'accommodation_id' => ['required', 'integer', 'exists:accommodations,id'],
            'max_infant_age' => ['required', 'integer'],
            'max_child_age' => ['required', 'integer'],
            'infant_when_disabled' => ['required', 'string'],
            'child_when_disabled' => ['required', 'string'],
            'infant_service_condition' => ['required', 'string'],
            'child_service_condition' => ['required', 'string'],
            'max_children_covered' => ['nullable', 'integer'],
            'max_infants_covered' => ['nullable', 'integer'],
            'infant_pricing_type' => ['required', 'string'],
            'infant_pricing_value' => ['nullable', 'integer'],
            'child_pricing_type' => ['required', 'string'],
            'child_pricing_value' => ['nullable', 'integer'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'integer'],
        ];
    }
}
