<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRoomTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id' => 'sometimes|nullable',
            'accommodation_id' => 'sometimes|nullable|integer|exists:accommodations,id',
            'fa_name' => 'sometimes|nullable',
            'en_name' => 'sometimes|nullable',
            'capacity' => 'sometimes|nullable|integer',
            'extra_capacity' => 'sometimes|nullable|integer',
            'single_bed_count' => 'sometimes|nullable|integer',
            'double_bed_count' => 'sometimes|nullable|integer',
            'sofa_bed_count' => 'sometimes|nullable|integer',
            'out_of_service' => 'sometimes|nullable|boolean',
            'created_at' => 'sometimes|nullable|date',
            'updated_at' => 'sometimes|nullable|date',
        ];
    }
}
