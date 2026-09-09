<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Helpers\ApiResponseHelper;
use App\Models\Rule;

/**
 * @mixin Rule
 */
class RuleResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'hotel_id' => $this->hotel_id,
            'provider_rule_id' => $this->provider_rule_id,
            'rule_id' => $this->rule_id,
            'type' => $this->type,
            'category' => RuleCategoryResource::make($this->whenLoaded('category')),
            'name' => $this->name,
            'name_ar' => $this->name_ar,
            'name_en' => $this->name_en,
            'conditions' => $this->conditions,
            'room_type_id' => $this->room_type_id,
            'rate_plan_id' => $this->rate_plan_id,
            'description' => $this->description,
            'description_ar' => $this->description_ar,
            'description_en' => $this->description_en,
            'status' => ApiResponseHelper::getStatus((bool) $this->status),
            'hotel' => AccommodationResource::make($this->whenLoaded('hotel')),
        ];
    }
}
