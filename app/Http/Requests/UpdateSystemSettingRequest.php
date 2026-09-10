<?php

namespace App\Http\Requests;

use App\Models\SystemSetting;
use App\Support\System\SystemSettingValueType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSystemSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var SystemSetting|null $setting */
        $setting = $this->route('system_setting');

        return [
            'key' => [
                'sometimes',
                'required',
                'string',
                'max:150',
                Rule::unique('system_settings', 'key')->ignore($setting?->id),
            ],
            'group' => ['sometimes', 'required', 'string', 'max:100'],
            'value' => ['sometimes', 'required'],
            'value_type' => ['sometimes', 'required', 'string', Rule::in(SystemSettingValueType::values())],
            'is_active' => ['sometimes', 'required', 'boolean'],
        ];
    }
}
