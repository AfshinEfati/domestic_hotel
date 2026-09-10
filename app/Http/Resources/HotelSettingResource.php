<?php

namespace App\Http\Resources;

use App\Helpers\StatusHelper;
use Illuminate\Http\Resources\Json\JsonResource;

class HotelSettingResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'key' => $this->key,
            'group' => $this->group,
            'value' => $this->value,
            'value_type' => $this->value_type,
            'is_active' => (bool) $this->is_active,
            'created_at' => StatusHelper::formatDates($this->created_at),
            'updated_at' => StatusHelper::formatDates($this->updated_at),
        ];
    }
}
