<?php

namespace App\Http\Requests;

use App\Models\ProviderPricingRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProviderPricingRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var ProviderPricingRule|null $pricingRule */
        $pricingRule = $this->route('provider_pricing_rule');

        return [
            'provider_id' => [
                'sometimes',
                'required',
                'integer',
                'exists:providers,id',
                Rule::unique('provider_pricing_rules', 'provider_id')->ignore($pricingRule?->id),
            ],
            'percentage' => ['sometimes', 'required', 'numeric', 'min:0'],
            'fixed_amount' => ['sometimes', 'required', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'required', 'boolean'],
        ];
    }
}
