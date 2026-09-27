<?php

namespace App\Http\Resources;

use App\Helpers\StatusHelper;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseManualRuleResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'provider_id' => $this->provider_id,
            'accommodation_id' => $this->accommodation_id,
            'minimum_amount' => $this->minimum_amount,
            'maximum_amount' => $this->maximum_amount,
            'start_time' => $this->start_time?->format('H:i'),
            'end_time' => $this->end_time?->format('H:i'),
            'is_active' => (bool) $this->is_active,
            'provider' => $this->relationLoaded('provider') && $this->provider !== null
                ? [
                    'id' => $this->provider->id,
                    'fa_name' => $this->provider->fa_name,
                    'en_name' => $this->provider->en_name,
                    'code' => $this->provider->code,
                ]
                : null,
            'accommodation' => $this->relationLoaded('accommodation') && $this->accommodation !== null
                ? [
                    'id' => $this->accommodation->id,
                    'fa_name' => $this->accommodation->fa_name,
                    'en_name' => $this->accommodation->en_name,
                ]
                : null,
            'created_at' => StatusHelper::formatDates($this->created_at),
            'updated_at' => StatusHelper::formatDates($this->updated_at),
        ];
    }
}
