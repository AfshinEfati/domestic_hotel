<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRatePlanRequest extends FormRequest
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
            'meal_type' => 'nullable',
            'food_board_type' => 'nullable',
            'cancelable' => 'nullable|boolean',
            'sleeps' => 'nullable|integer',
            'min_stay' => 'nullable|integer',
            'max_stay' => 'nullable|integer',
            'facilities' => 'nullable|array',
            'created_at' => 'nullable|date',
            'updated_at' => 'nullable|date',
        ];
    }
}
