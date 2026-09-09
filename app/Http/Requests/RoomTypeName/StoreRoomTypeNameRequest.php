<?php

namespace App\Http\Requests\RoomTypeName;

use Illuminate\Foundation\Http\FormRequest;

class StoreRoomTypeNameRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'fa_name' => ['required', 'string', 'unique:room_type_names,fa_name'],
            'en_name' => ['nullable', 'string'],
        ];
    }
}
