<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProviderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id' => 'sometimes|nullable',
            'fa_name' => 'sometimes|nullable',
            'en_name' => 'sometimes|nullable',
            'code' => 'sometimes|nullable',
            'config' => 'sometimes|nullable|array',
            'is_active' => 'sometimes|nullable|boolean',
            'is_online' => 'sometimes|nullable|boolean',
            'created_at' => 'sometimes|nullable|date',
            'updated_at' => 'sometimes|nullable|date',
            'auth_token' => 'sometimes|nullable',
            'expire_at' => 'sometimes|nullable|date',
        ];
    }
}
