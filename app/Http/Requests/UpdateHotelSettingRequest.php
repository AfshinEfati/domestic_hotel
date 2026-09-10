<?php

namespace App\Http\Requests;

use App\Models\HotelSetting;
use App\Support\Hotel\HotelSettingValueType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateHotelSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var HotelSetting|null $setting */
        $setting = $this->route('hotel_setting');

        return [
            'key' => [
                'sometimes',
                'required',
                'string',
                'max:150',
                Rule::unique('hotel_settings', 'key')->ignore($setting?->id),
            ],
            'group' => ['sometimes', 'required', 'string', 'max:100'],
            'value' => ['sometimes', 'required'],
            'value_type' => ['sometimes', 'required', 'string', Rule::in(HotelSettingValueType::values())],
            'is_active' => ['sometimes', 'required', 'boolean'],
        ];
    }
}
