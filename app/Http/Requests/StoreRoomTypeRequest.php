<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRoomTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id' => 'nullable',
            'accommodation_id' => 'required|integer|exists:accommodations,id',
            'fa_name' => 'nullable',
            'en_name' => 'nullable',
            'capacity' => 'nullable|integer',
            'extra_capacity' => 'nullable|integer',
            'single_bed_count' => 'nullable|integer',
            'double_bed_count' => 'nullable|integer',
            'sofa_bed_count' => 'nullable|integer',
            'out_of_service' => 'nullable|boolean',
            'created_at' => 'nullable|date',
            'updated_at' => 'nullable|date',
        ];
    }
}
