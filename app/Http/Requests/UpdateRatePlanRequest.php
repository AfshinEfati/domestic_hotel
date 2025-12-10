<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRatePlanRequest extends FormRequest
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
            'meal_type' => 'sometimes|nullable',
            'food_board_type' => 'sometimes|nullable',
            'cancelable' => 'sometimes|nullable|boolean',
            'sleeps' => 'sometimes|nullable|integer',
            'min_stay' => 'sometimes|nullable|integer',
            'max_stay' => 'sometimes|nullable|integer',
            'facilities' => 'sometimes|nullable|array',
            'created_at' => 'sometimes|nullable|date',
            'updated_at' => 'sometimes|nullable|date',
        ];
    }
}
