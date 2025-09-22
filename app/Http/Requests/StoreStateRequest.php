<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreStateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id' => 'nullable',
            'country_id' => 'required|integer|exists:countries,id',
            'fa_name' => 'nullable',
            'en_name' => 'nullable',
            'created_at' => 'nullable|date',
            'updated_at' => 'nullable|date',
        ];
    }
}
