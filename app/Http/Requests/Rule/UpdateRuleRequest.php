<?php

namespace App\Http\Requests\Rule;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'hotel_id' => ['sometimes', 'required', 'integer', 'exists:accommodations,id'],
            'rule_category_id' => ['sometimes', 'required', 'integer', 'exists:rule_categories,id'],
            'provider_rule_id' => ['sometimes', 'required', 'string'],
            'rule_id' => ['sometimes', 'nullable', 'integer'],
            'type' => ['sometimes', 'nullable', 'string', 'max:50'],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'name_ar' => ['sometimes', 'nullable', 'string', 'max:255'],
            'name_en' => ['sometimes', 'nullable', 'string', 'max:255'],
            'conditions' => ['sometimes', 'nullable', 'array'],
            'room_type_id' => ['sometimes', 'nullable', 'string', 'max:64'],
            'rate_plan_id' => ['sometimes', 'nullable', 'string', 'max:64'],
            'description' => ['sometimes', 'nullable', 'string'],
            'description_ar' => ['sometimes', 'nullable', 'string'],
            'description_en' => ['sometimes', 'nullable', 'string'],
            'status' => ['sometimes', 'nullable', 'boolean'],
        ];
    }
}
