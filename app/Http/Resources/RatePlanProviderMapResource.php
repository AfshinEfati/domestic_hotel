<?php

namespace App\Http\Resources;

use App\Helpers\StatusHelper;
use Illuminate\Http\Resources\Json\JsonResource;

class RatePlanProviderMapResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'rate_plan_id' => $this->rate_plan_id,
            'provider_id' => $this->provider_id,
            'provider_rate_plan_id' => $this->provider_rate_plan_id,
            'fa_name' => $this->fa_name,
            'en_name' => $this->en_name,
            'created_at' => StatusHelper::formatDates($this->created_at),
            'updated_at' => StatusHelper::formatDates($this->updated_at),
            'rate_plan' => class_exists('App\\Http\\Resources\\RatePlanResource')
                ? new \App\Http\Resources\RatePlanResource($this->whenLoaded('ratePlan'))
                : $this->whenLoaded('ratePlan'),
            'provider' => class_exists('App\\Http\\Resources\\ProviderResource')
                ? new \App\Http\Resources\ProviderResource($this->whenLoaded('provider'))
                : $this->whenLoaded('provider'),
        ];
    }
}
