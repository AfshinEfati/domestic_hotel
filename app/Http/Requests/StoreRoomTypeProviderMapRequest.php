<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRoomTypeProviderMapRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id' => 'nullable',
            'room_type_id' => 'required|integer|exists:room_types,id',
            'provider_id' => 'required|integer|exists:providers,id',
            'provider_room_type_id' => 'nullable',
            'fa_name' => 'nullable',
            'en_name' => 'nullable',
            'created_at' => 'nullable|date',
            'updated_at' => 'nullable|date',
        ];
    }
}
