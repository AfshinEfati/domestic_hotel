<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRoomTypeProviderMapRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id' => 'sometimes|nullable',
            'room_type_id' => 'sometimes|nullable|integer|exists:room_types,id',
            'provider_id' => 'sometimes|nullable|integer|exists:providers,id',
            'provider_room_type_id' => 'sometimes|nullable',
            'fa_name' => 'sometimes|nullable',
            'en_name' => 'sometimes|nullable',
            'created_at' => 'sometimes|nullable|date',
            'updated_at' => 'sometimes|nullable|date',
        ];
    }
}
