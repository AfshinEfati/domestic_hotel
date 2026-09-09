<?php

namespace App\Http\Requests\HotelChildPolicy;

use Illuminate\Foundation\Http\FormRequest;

class UpdateHotelChildPolicyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'accommodation_id' => ['sometimes', 'required', 'integer', 'exists:accommodations,id'],
            'max_infant_age' => ['sometimes', 'required', 'integer'],
            'max_child_age' => ['sometimes', 'required', 'integer'],
            'infant_when_disabled' => ['sometimes', 'required', 'string'],
            'child_when_disabled' => ['sometimes', 'required', 'string'],
            'infant_service_condition' => ['sometimes', 'required', 'string'],
            'child_service_condition' => ['sometimes', 'required', 'string'],
            'max_children_covered' => ['sometimes', 'nullable', 'integer'],
            'max_infants_covered' => ['sometimes', 'nullable', 'integer'],
            'infant_pricing_type' => ['sometimes', 'required', 'string'],
            'infant_pricing_value' => ['sometimes', 'nullable', 'integer'],
            'child_pricing_type' => ['sometimes', 'required', 'string'],
            'child_pricing_value' => ['sometimes', 'nullable', 'integer'],
            'description' => ['sometimes', 'nullable', 'string'],
            'status' => ['sometimes', 'required', 'integer'],
        ];
    }
}
