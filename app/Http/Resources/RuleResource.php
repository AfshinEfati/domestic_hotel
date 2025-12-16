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
            'title' => $this->title,
            'name' => $this->name,
            'is_active' => ApiResponseHelper::getStatus((bool) $this->is_active),
            'accommodations' => class_exists('App\Http\Resources\AccommodationResource')
                ? new \App\Http\Resources\AccommodationResource($this->whenLoaded('accommodations'))
                : $this->whenLoaded('accommodations'),
            'morphTo' => class_exists('App\Http\Resources\RuleResource')
                ? new \App\Http\Resources\RuleResource($this->whenLoaded('morphTo'))
                : $this->whenLoaded('morphTo'),
        ];
    }
}
