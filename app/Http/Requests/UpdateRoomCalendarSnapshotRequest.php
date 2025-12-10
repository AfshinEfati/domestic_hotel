<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRoomCalendarSnapshotRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id' => 'sometimes|nullable',
            'room_calendar_id' => 'sometimes|nullable|integer|exists:room_calendars,id',
            'provider_id' => 'sometimes|nullable|integer|exists:providers,id',
            'day' => 'sometimes|nullable|date',
            'payload' => 'sometimes|nullable|array',
            'created_at' => 'sometimes|nullable|date',
            'updated_at' => 'sometimes|nullable|date',
        ];
    }
}
