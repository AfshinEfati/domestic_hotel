<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePurchaseManualRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'provider_id' => ['nullable', 'integer', 'exists:providers,id'],
            'accommodation_id' => ['nullable', 'integer', 'exists:accommodations,id'],
            'minimum_amount' => ['nullable', 'integer', 'min:0'],
            'maximum_amount' => ['nullable', 'integer', 'min:0'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
