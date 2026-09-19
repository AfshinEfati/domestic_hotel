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
            'config.availability_rate_limit' => 'sometimes|nullable|array',
            'config.availability_rate_limit.max_requests' => 'sometimes|nullable|integer|min:1|max:10',
            'config.availability_rate_limit.window_minutes' => 'sometimes|nullable|integer|min:1',
            'config.price_refresh' => 'sometimes|nullable|array',
            'config.price_refresh.default_days' => 'sometimes|integer|min:1|max:3650',
            'config.price_refresh.dispatch_limit' => 'sometimes|integer|min:1|max:100',
            'config.price_refresh.claim_minutes' => 'sometimes|integer|min:5|max:1440',
            'config.price_refresh.failure_backoff_minutes' => 'sometimes|integer|min:1|max:1440',
            'config.price_refresh.api_cooldown_minutes' => 'sometimes|integer|min:1|max:1440',
            'config.price_refresh.scheduler_enabled' => 'sometimes|boolean',
            'is_active' => 'sometimes|nullable|boolean',
            'is_online' => 'sometimes|nullable|boolean',
            'created_at' => 'sometimes|nullable|date',
            'updated_at' => 'sometimes|nullable|date',
            'auth_token' => 'sometimes|nullable',
            'expire_at' => 'sometimes|nullable|date',
        ];
    }
}
