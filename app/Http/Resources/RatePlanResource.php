<?php

namespace App\Http\Resources;

use App\Helpers\StatusHelper;
use Illuminate\Http\Resources\Json\JsonResource;

class RatePlanResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'accommodation_id' => $this->accommodation_id,
            'fa_name' => $this->fa_name,
            'en_name' => $this->en_name,
            'meal_type' => $this->meal_type,
            'food_board_type' => $this->food_board_type,
            'cancelable' => $this->cancelable === null ? null : StatusHelper::getStatus((bool) $this->cancelable),
            'sleeps' => $this->sleeps,
            'min_stay' => $this->min_stay,
            'max_stay' => $this->max_stay,
            'facilities' => $this->facilities,
            'created_at' => StatusHelper::formatDates($this->created_at),
            'updated_at' => StatusHelper::formatDates($this->updated_at),
            'accommodation' => class_exists('App\\Http\\Resources\\AccommodationResource')
                ? new \App\Http\Resources\AccommodationResource($this->whenLoaded('accommodation'))
                : $this->whenLoaded('accommodation'),
        ];
    }
}
