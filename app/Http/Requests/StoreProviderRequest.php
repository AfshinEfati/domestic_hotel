<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProviderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id' => 'nullable',
            'fa_name' => 'nullable',
            'en_name' => 'nullable',
            'code' => 'nullable',
            'config' => 'nullable|array',
            'is_active' => 'nullable|boolean',
            'is_online' => 'nullable|boolean',
            'created_at' => 'nullable|date',
            'updated_at' => 'nullable|date',
            'auth_token' => 'nullable',
            'expire_at' => 'nullable|date',
        ];
    }
}
