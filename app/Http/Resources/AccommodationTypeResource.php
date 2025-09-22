<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Helpers\StatusHelper;

class AccommodationTypeResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'fa_name' => $this->fa_name,
            'en_name' => $this->en_name,
            'created_at' => StatusHelper::formatDates($this->created_at),
            'updated_at' => StatusHelper::formatDates($this->updated_at),
            'accommodations' => class_exists('App\Http\Resources\AccommodationResource')
                ? new AccommodationResource($this->whenLoaded('accommodations'))
                : $this->whenLoaded('accommodations'),
        ];
    }
}
