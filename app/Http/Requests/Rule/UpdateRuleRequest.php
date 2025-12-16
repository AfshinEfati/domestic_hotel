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
            'title' => ['sometimes', 'required', 'string'],
            'name' => ['sometimes', 'required', 'string', 'unique:rules,name,' . $this->route('rule')->id],
            'is_active' => ['sometimes', 'required', 'boolean'],
        ];
    }
}
