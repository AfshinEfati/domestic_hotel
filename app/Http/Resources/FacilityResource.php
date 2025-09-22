<?php

namespace App\Http\Resources;

use App\Helpers\StatusHelper;
use Illuminate\Http\Resources\Json\JsonResource;

class FacilityResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'facility_group_id' => $this->facility_group_id,
            'fa_name' => $this->fa_name,
            'en_name' => $this->en_name,
            'created_at' => StatusHelper::formatDates($this->created_at),
            'updated_at' => StatusHelper::formatDates($this->updated_at),
            'group' => class_exists('App\\Http\\Resources\\FacilityGroupResource')
                ? new \App\Http\Resources\FacilityGroupResource($this->whenLoaded('group'))
                : $this->whenLoaded('group'),
        ];
    }
}
