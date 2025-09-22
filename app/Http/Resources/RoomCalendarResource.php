<?php

namespace App\Http\Resources;

use App\Helpers\StatusHelper;
use Illuminate\Http\Resources\Json\JsonResource;

class RoomCalendarResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'accommodation_id' => $this->accommodation_id,
            'room_type_id' => $this->room_type_id,
            'rate_plan_id' => $this->rate_plan_id,
            'day' => StatusHelper::formatDates($this->day),
            'rack_rate' => $this->rack_rate,
            'daily_rate' => $this->daily_rate,
            'grs_rate' => $this->grs_rate,
            'baby_cot_rack_rate' => $this->baby_cot_rack_rate,
            'baby_cot_daily_rate' => $this->baby_cot_daily_rate,
            'baby_cot_grs_rate' => $this->baby_cot_grs_rate,
            'extend_bed_rack_rate' => $this->extend_bed_rack_rate,
            'extend_bed_daily_rate' => $this->extend_bed_daily_rate,
            'extend_bed_grs_rate' => $this->extend_bed_grs_rate,
            'min_stay' => $this->min_stay,
            'max_stay' => $this->max_stay,
            'cta' => $this->cta === null ? null : StatusHelper::getStatus((bool) $this->cta),
            'ctd' => $this->ctd === null ? null : StatusHelper::getStatus((bool) $this->ctd),
            'closed' => $this->closed === null ? null : StatusHelper::getStatus((bool) $this->closed),
            'inventory' => $this->inventory,
            'provider_id' => $this->provider_id,
            'provider_property_id' => $this->provider_property_id,
            'provider_room_type_id' => $this->provider_room_type_id,
            'provider_rate_plan_id' => $this->provider_rate_plan_id,
            'created_at' => StatusHelper::formatDates($this->created_at),
            'updated_at' => StatusHelper::formatDates($this->updated_at),
            'accommodation' => class_exists('App\\Http\\Resources\\AccommodationResource')
                ? new \App\Http\Resources\AccommodationResource($this->whenLoaded('accommodation'))
                : $this->whenLoaded('accommodation'),
            'room_type' => class_exists('App\\Http\\Resources\\RoomTypeResource')
                ? new \App\Http\Resources\RoomTypeResource($this->whenLoaded('roomType'))
                : $this->whenLoaded('roomType'),
            'rate_plan' => class_exists('App\\Http\\Resources\\RatePlanResource')
                ? new \App\Http\Resources\RatePlanResource($this->whenLoaded('ratePlan'))
                : $this->whenLoaded('ratePlan'),
            'provider' => class_exists('App\\Http\\Resources\\ProviderResource')
                ? new \App\Http\Resources\ProviderResource($this->whenLoaded('provider'))
                : $this->whenLoaded('provider'),
        ];
    }
}
