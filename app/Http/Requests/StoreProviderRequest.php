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
            'config.availability_rate_limit' => 'nullable|array',
            'config.availability_rate_limit.max_requests' => 'nullable|integer|min:1|max:10',
            'config.availability_rate_limit.window_minutes' => 'nullable|integer|min:1',
            'is_active' => 'nullable|boolean',
            'is_online' => 'nullable|boolean',
            'created_at' => 'nullable|date',
            'updated_at' => 'nullable|date',
            'auth_token' => 'nullable',
            'expire_at' => 'nullable|date',
        ];
    }
}
