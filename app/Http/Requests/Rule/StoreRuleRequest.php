<?php

namespace App\Http\Requests\Rule;

use Illuminate\Foundation\Http\FormRequest;

class StoreRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'hotel_id' => ['required', 'integer', 'exists:accommodations,id'],
            'rule_category_id' => ['required', 'integer', 'exists:rule_categories,id'],
            'provider_rule_id' => ['required', 'string'],
            'rule_id' => ['nullable', 'integer'],
            'type' => ['nullable', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:255'],
            'name_ar' => ['nullable', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'conditions' => ['nullable', 'array'],
            'room_type_id' => ['nullable', 'string', 'max:64'],
            'rate_plan_id' => ['nullable', 'string', 'max:64'],
            'description' => ['nullable', 'string'],
            'description_ar' => ['nullable', 'string'],
            'description_en' => ['nullable', 'string'],
            'status' => ['nullable', 'boolean'],
        ];
    }
}
