<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRoomCalendarRequest extends FormRequest
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
            'room_type_id' => 'sometimes|nullable|integer|exists:room_types,id',
            'rate_plan_id' => 'sometimes|nullable|integer|exists:rate_plans,id',
            'day' => 'sometimes|nullable|date',
            'rack_rate' => 'sometimes|nullable|integer',
            'daily_rate' => 'sometimes|nullable|integer',
            'grs_rate' => 'sometimes|nullable|integer',
            'baby_cot_rack_rate' => 'sometimes|nullable|integer',
            'baby_cot_daily_rate' => 'sometimes|nullable|integer',
            'baby_cot_grs_rate' => 'sometimes|nullable|integer',
            'extend_bed_rack_rate' => 'sometimes|nullable|integer',
            'extend_bed_daily_rate' => 'sometimes|nullable|integer',
            'extend_bed_grs_rate' => 'sometimes|nullable|integer',
            'min_stay' => 'sometimes|nullable|integer',
            'max_stay' => 'sometimes|nullable|integer',
            'cta' => 'sometimes|nullable|boolean',
            'ctd' => 'sometimes|nullable|boolean',
            'closed' => 'sometimes|nullable|boolean',
            'inventory' => 'sometimes|nullable|integer',
            'provider_id' => 'sometimes|nullable|integer|exists:providers,id',
            'provider_property_id' => 'sometimes|nullable',
            'provider_room_type_id' => 'sometimes|nullable',
            'provider_rate_plan_id' => 'sometimes|nullable',
            'created_at' => 'sometimes|nullable|date',
            'updated_at' => 'sometimes|nullable|date',
        ];
    }
}
