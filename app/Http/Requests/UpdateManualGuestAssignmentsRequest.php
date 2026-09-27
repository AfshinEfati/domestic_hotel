<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateManualGuestAssignmentsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'acc_code' => ['required', 'string', 'max:100'],
            'rooms' => ['required', 'array', 'min:1'],
            'rooms.*.room_id' => ['required', 'integer', 'exists:reservation_rooms,id'],
            'rooms.*.guest_ids' => ['required', 'array'],
            'rooms.*.guest_ids.*' => ['integer', 'distinct', 'exists:reservation_guests,id'],
        ];
    }
}
