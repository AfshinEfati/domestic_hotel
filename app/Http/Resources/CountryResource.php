<?php

namespace App\Http\Resources;

use App\Helpers\StatusHelper;
use Illuminate\Http\Resources\Json\JsonResource;

class CountryResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'fa_name' => $this->fa_name,
            'en_name' => $this->en_name,
            'iso2' => $this->iso2,
            'iso3' => $this->iso3,
            'created_at' => StatusHelper::formatDates($this->created_at),
            'updated_at' => StatusHelper::formatDates($this->updated_at),
        ];
    }
}
