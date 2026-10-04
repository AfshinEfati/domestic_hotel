<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRoomCalendarRequest extends FormRequest
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
            'room_type_id' => 'required|integer|exists:room_types,id',
            'rate_plan_id' => 'required|integer|exists:rate_plans,id',
            'day' => 'required|date',
            'rack_rate' => 'nullable|integer|min:0',
            'daily_rate' => 'nullable|integer|min:0',
            'grs_rate' => 'nullable|integer|min:0',
            'baby_cot_rack_rate' => 'nullable|integer|min:0',
            'baby_cot_daily_rate' => 'nullable|integer|min:0',
            'baby_cot_grs_rate' => 'nullable|integer|min:0',
            'child_daily_rate' => 'nullable|integer|min:0',
            'infant_daily_rate' => 'nullable|integer|min:0',
            'extend_bed_rack_rate' => 'nullable|integer|min:0',
            'extend_bed_daily_rate' => 'nullable|integer|min:0',
            'extend_bed_grs_rate' => 'nullable|integer|min:0',
            'min_stay' => 'nullable|integer|min:1',
            'max_stay' => 'nullable|integer|min:1',
            'cta' => 'nullable|boolean',
            'ctd' => 'nullable|boolean',
            'closed' => 'nullable|boolean',
            'inventory' => 'nullable|integer|min:0',
            'provider_id' => 'nullable|integer|exists:providers,id',
            'provider_property_id' => 'nullable|string|max:64',
            'provider_room_type_id' => 'nullable|string|max:64',
            'provider_rate_plan_id' => 'nullable|string|max:64',
            'created_at' => 'nullable|date',
            'updated_at' => 'nullable|date',
        ];
    }
}
