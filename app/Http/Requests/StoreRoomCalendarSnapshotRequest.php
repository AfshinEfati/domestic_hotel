<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRoomCalendarSnapshotRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id' => 'nullable',
            'room_calendar_id' => 'required|integer|exists:room_calendars,id',
            'provider_id' => 'required|integer|exists:providers,id',
            'day' => 'required|date',
            'payload' => 'nullable|array',
            'created_at' => 'nullable|date',
            'updated_at' => 'nullable|date',
        ];
    }
}
