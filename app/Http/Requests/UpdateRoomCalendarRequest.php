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
            'rack_rate' => 'sometimes|nullable|integer|min:0',
            'daily_rate' => 'sometimes|nullable|integer|min:0',
            'grs_rate' => 'sometimes|nullable|integer|min:0',
            'baby_cot_rack_rate' => 'sometimes|nullable|integer|min:0',
            'baby_cot_daily_rate' => 'sometimes|nullable|integer|min:0',
            'baby_cot_grs_rate' => 'sometimes|nullable|integer|min:0',
            'child_daily_rate' => 'sometimes|nullable|integer|min:0',
            'infant_daily_rate' => 'sometimes|nullable|integer|min:0',
            'extend_bed_rack_rate' => 'sometimes|nullable|integer|min:0',
            'extend_bed_daily_rate' => 'sometimes|nullable|integer|min:0',
            'extend_bed_grs_rate' => 'sometimes|nullable|integer|min:0',
            'min_stay' => 'sometimes|nullable|integer|min:1',
            'max_stay' => 'sometimes|nullable|integer|min:1',
            'cta' => 'sometimes|nullable|boolean',
            'ctd' => 'sometimes|nullable|boolean',
            'closed' => 'sometimes|nullable|boolean',
            'inventory' => 'sometimes|nullable|integer|min:0',
            'provider_id' => 'sometimes|nullable|integer|exists:providers,id',
            'provider_property_id' => 'sometimes|nullable|string|max:64',
            'provider_room_type_id' => 'sometimes|nullable|string|max:64',
            'provider_rate_plan_id' => 'sometimes|nullable|string|max:64',
            'created_at' => 'sometimes|nullable|date',
            'updated_at' => 'sometimes|nullable|date',
        ];
    }
}
