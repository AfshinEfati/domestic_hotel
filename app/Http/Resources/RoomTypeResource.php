<?php

namespace App\Http\Resources;

use App\Helpers\ApiResponseHelper;
use App\Models\RoomType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin RoomType */
class RoomTypeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'fa_name' => $this->fa_name,
            'en_name' => $this->en_name,
            'capacity' => $this->capacity,
            'extra_capacity' => $this->extra_capacity,
            'single_bed_count' => $this->single_bed_count,
            'double_bed_count' => $this->double_bed_count,
            'sofa_bed_count' => $this->sofa_bed_count,
            'out_of_service' => $this->out_of_service,
            'created_at' => ApiResponseHelper::formatDates($this->created_at),
            'updated_at' => ApiResponseHelper::formatDates($this->updated_at),
            'roomTypeName' => new RoomTypeNameResource($this->whenLoaded('roomTypeName')),
        ];
    }
}
