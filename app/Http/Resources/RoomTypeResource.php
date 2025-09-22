<?php

namespace App\Http\Resources;

use App\Helpers\StatusHelper;
use Illuminate\Http\Resources\Json\JsonResource;

class RoomTypeResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'accommodation_id' => $this->accommodation_id,
            'fa_name' => $this->fa_name,
            'en_name' => $this->en_name,
            'capacity' => $this->capacity,
            'extra_capacity' => $this->extra_capacity,
            'single_bed_count' => $this->single_bed_count,
            'double_bed_count' => $this->double_bed_count,
            'sofa_bed_count' => $this->sofa_bed_count,
            'out_of_service' => $this->out_of_service === null ? null : StatusHelper::getStatus((bool) $this->out_of_service),
            'created_at' => StatusHelper::formatDates($this->created_at),
            'updated_at' => StatusHelper::formatDates($this->updated_at),
            'accommodation' => class_exists('App\\Http\\Resources\\AccommodationResource')
                ? new \App\Http\Resources\AccommodationResource($this->whenLoaded('accommodation'))
                : $this->whenLoaded('accommodation'),
        ];
    }
}
