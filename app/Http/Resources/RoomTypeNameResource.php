<?php

namespace App\Http\Resources;

use App\Helpers\ApiResponseHelper;
use App\Models\RoomTypeName;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin RoomTypeName */
class RoomTypeNameResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'fa_name' => $this->fa_name,
            'en_name' => $this->en_name,
            'created_at' => ApiResponseHelper::formatDates($this->created_at),
            'updated_at' => ApiResponseHelper::formatDates($this->updated_at),
        ];
    }
}
