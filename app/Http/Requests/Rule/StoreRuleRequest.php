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
            'title' => ['required', 'string'],
            'name' => ['required', 'string', 'unique:rules,name'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
