<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRoomTypeNameRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'fa_name' => ['required', 'unique:room_type_names,fa_name,' . $this->route('room_type_name')->id],
            'en_name' => ['nullable', 'unique:room_type_names,en_name,' . $this->route('room_type_name')->id],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
