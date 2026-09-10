<?php

namespace App\Http\Requests;

use App\Support\Hotel\HotelSettingValueType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreHotelSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'key' => ['required', 'string', 'max:150', 'unique:hotel_settings,key'],
            'group' => ['required', 'string', 'max:100'],
            'value' => ['required'],
            'value_type' => ['required', 'string', Rule::in(HotelSettingValueType::values())],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
