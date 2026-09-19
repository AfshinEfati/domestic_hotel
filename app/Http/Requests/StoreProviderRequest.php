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
            'config.price_refresh' => 'nullable|array',
            'config.price_refresh.default_days' => 'sometimes|integer|min:1|max:3650',
            'config.price_refresh.dispatch_limit' => 'sometimes|integer|min:1|max:100',
            'config.price_refresh.claim_minutes' => 'sometimes|integer|min:5|max:1440',
            'config.price_refresh.failure_backoff_minutes' => 'sometimes|integer|min:1|max:1440',
            'config.price_refresh.api_cooldown_minutes' => 'sometimes|integer|min:1|max:1440',
            'config.price_refresh.scheduler_enabled' => 'sometimes|boolean',
            'is_active' => 'nullable|boolean',
            'is_online' => 'nullable|boolean',
            'created_at' => 'nullable|date',
            'updated_at' => 'nullable|date',
            'auth_token' => 'nullable',
            'expire_at' => 'nullable|date',
        ];
    }
}
