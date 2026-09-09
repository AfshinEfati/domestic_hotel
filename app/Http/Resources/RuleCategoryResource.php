<?php

namespace App\Http\Resources;

use App\Models\RuleCategory;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin RuleCategory
 */
class RuleCategoryResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'name_en' => $this->name_en,
            'name_ar' => $this->name_ar,
        ];
    }
}
