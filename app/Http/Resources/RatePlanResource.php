<?php

namespace App\Http\Resources;

use App\Helpers\StatusHelper;
use App\Models\RatePlan;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin RatePlan */
class RatePlanResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'accommodation_id' => $this->accommodation_id,
            'fa_name' => $this->fa_name,
            'en_name' => $this->en_name,
            'is_foreign_guest'=>$this->is_foreign_guest,
            'created_at' => StatusHelper::formatDates($this->created_at),
            'updated_at' => StatusHelper::formatDates($this->updated_at),
            'accommodation' => AccommodationResource::make($this->whenLoaded('accommodation')),
        ];
    }
}
