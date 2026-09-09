<?php

namespace App\Http\Resources;

use App\Helpers\ApiResponseHelper;
use App\Models\HotelChildPolicy;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin HotelChildPolicy */
class HotelChildPolicyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'max_infant_age' => $this->max_infant_age,
            'max_child_age' => $this->max_child_age,
            'infant_when_disabled' => $this->infant_when_disabled,
            'child_when_disabled' => $this->child_when_disabled,
            'infant_service_condition' => $this->infant_service_condition,
            'child_service_condition' => $this->child_service_condition,
            'max_children_covered' => $this->max_children_covered,
            'max_infants_covered' => $this->max_infants_covered,
            'infant_pricing_type' => $this->infant_pricing_type,
            'infant_pricing_value' => $this->infant_pricing_value,
            'child_pricing_type' => $this->child_pricing_type,
            'child_pricing_value' => $this->child_pricing_value,
            'description' => $this->description,
            'status' => ApiResponseHelper::getStatus((bool) $this->status),
            'created_at' => ApiResponseHelper::formatDates($this->created_at),
            'updated_at' => ApiResponseHelper::formatDates($this->updated_at),
        ];
    }
}
