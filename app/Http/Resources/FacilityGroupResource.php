<?php

namespace App\Http\Resources;

use App\Helpers\StatusHelper;
use Illuminate\Http\Resources\Json\JsonResource;

class FacilityGroupResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'fa_name' => $this->fa_name,
            'en_name' => $this->en_name,
            'created_at' => StatusHelper::formatDates($this->created_at),
            'updated_at' => StatusHelper::formatDates($this->updated_at),
            'facilities' => class_exists('App\\Http\\Resources\\FacilityResource')
                ? FacilityResource::collection($this->whenLoaded('facilities'))
                : $this->whenLoaded('facilities'),
        ];
    }
}
