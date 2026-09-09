<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateRoomTypeNameRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'fa_name' => ['required', 'unique:room_type_names,fa_name'],
            'en_name' => ['nullable', 'unique:room_type_names,en_name'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
