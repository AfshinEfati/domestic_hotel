<?php

namespace App\DTOs;

use Illuminate\Http\Request;

class RuleDTO
{
    public function __construct(
        public readonly mixed $hotel_id = null,
        public readonly mixed $rule_category_id = null,
        public readonly mixed $provider_rule_id = null,
        public readonly mixed $rule_id = null,
        public readonly mixed $type = null,
        public readonly mixed $name = null,
        public readonly mixed $name_ar = null,
        public readonly mixed $name_en = null,
        public readonly mixed $conditions = null,
        public readonly mixed $room_type_id = null,
        public readonly mixed $rate_plan_id = null,
        public readonly mixed $description = null,
        public readonly mixed $description_ar = null,
        public readonly mixed $description_en = null,
        public readonly mixed $status = null
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            hotel_id: $request->input('hotel_id'),
            rule_category_id: $request->input('rule_category_id'),
            provider_rule_id: $request->input('provider_rule_id'),
            rule_id: $request->input('rule_id'),
            type: $request->input('type'),
            name: $request->input('name'),
            name_ar: $request->input('name_ar'),
            name_en: $request->input('name_en'),
            conditions: $request->input('conditions'),
            room_type_id: $request->input('room_type_id'),
            rate_plan_id: $request->input('rate_plan_id'),
            description: $request->input('description'),
            description_ar: $request->input('description_ar'),
            description_en: $request->input('description_en'),
            status: $request->input('status'),
        );
    }

    public function toArray(): array
    {
        $out = [];
        if ($this->hotel_id !== null) { $out['hotel_id'] = $this->hotel_id; }
        if ($this->rule_category_id !== null) { $out['rule_category_id'] = $this->rule_category_id; }
        if ($this->provider_rule_id !== null) { $out['provider_rule_id'] = $this->provider_rule_id; }
        if ($this->rule_id !== null) { $out['rule_id'] = $this->rule_id; }
        if ($this->type !== null) { $out['type'] = $this->type; }
        if ($this->name !== null) { $out['name'] = $this->name; }
        if ($this->name_ar !== null) { $out['name_ar'] = $this->name_ar; }
        if ($this->name_en !== null) { $out['name_en'] = $this->name_en; }
        if ($this->conditions !== null) { $out['conditions'] = $this->conditions; }
        if ($this->room_type_id !== null) { $out['room_type_id'] = $this->room_type_id; }
        if ($this->rate_plan_id !== null) { $out['rate_plan_id'] = $this->rate_plan_id; }
        if ($this->description !== null) { $out['description'] = $this->description; }
        if ($this->description_ar !== null) { $out['description_ar'] = $this->description_ar; }
        if ($this->description_en !== null) { $out['description_en'] = $this->description_en; }
        if ($this->status !== null) { $out['status'] = $this->status; }
        return $out;
    }
}
