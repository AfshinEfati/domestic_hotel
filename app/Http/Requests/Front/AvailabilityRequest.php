<?php

namespace App\Http\Requests\Front;

use Illuminate\Foundation\Http\FormRequest;

class AvailabilityRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'state' => ['required', 'string'],
            'city' => ['required', 'string'],
            'check_in' => ['required', 'date_format:Y-m-d'],
            'check_out' => ['required', 'date_format:Y-m-d', 'after:check_in'],
            'rooms' => ['required', 'array','max:5', 'min:1'],
            'rooms.*.passengers' => ['required', 'array'],
            'rooms.*.passengers.*.type' => ['required', 'string', 'in:adult,adl,child,chd,infant,inf'],
            'rooms.*.passengers.*.age' => ['nullable', 'integer', 'min:0'],
            'rooms.*.passengers.*.title' => ['nullable', 'string'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
