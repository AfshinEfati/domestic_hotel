<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRatePlanProviderMapRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id' => 'nullable',
            'rate_plan_id' => 'required|integer|exists:rate_plans,id',
            'provider_id' => 'required|integer|exists:providers,id',
            'provider_rate_plan_id' => 'nullable',
            'fa_name' => 'nullable',
            'en_name' => 'nullable',
            'created_at' => 'nullable|date',
            'updated_at' => 'nullable|date',
        ];
    }
}
