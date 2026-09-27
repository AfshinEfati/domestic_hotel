<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePurchaseManualRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:150'],
            'provider_id' => ['sometimes', 'nullable', 'integer', 'exists:providers,id'],
            'accommodation_id' => ['sometimes', 'nullable', 'integer', 'exists:accommodations,id'],
            'minimum_amount' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'maximum_amount' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'start_time' => ['sometimes', 'nullable', 'date_format:H:i'],
            'end_time' => ['sometimes', 'nullable', 'date_format:H:i'],
            'is_active' => ['sometimes', 'required', 'boolean'],
        ];
    }
}
