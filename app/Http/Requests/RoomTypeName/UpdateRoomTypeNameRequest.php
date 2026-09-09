<?php

namespace App\Http\Requests\RoomTypeName;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRoomTypeNameRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'fa_name' => ['sometimes', 'required', 'string', 'unique:room_type_names,fa_name,' . $this->route('roomTypeName')->id],
            'en_name' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
