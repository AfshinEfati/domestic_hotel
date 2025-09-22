<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFacilityGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id' => 'nullable',
            'fa_name' => 'nullable',
            'en_name' => 'nullable',
            'created_at' => 'nullable|date',
            'updated_at' => 'nullable|date',
        ];
    }
}
