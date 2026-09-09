<?php

namespace App\DTOs;

use Illuminate\Http\Request;

class HotelChildPolicyDTO
{
    public function __construct(
        public readonly mixed $accommodation_id = null,
        public readonly mixed $max_infant_age = null,
        public readonly mixed $max_child_age = null,
        public readonly mixed $infant_when_disabled = null,
        public readonly mixed $child_when_disabled = null,
        public readonly mixed $infant_service_condition = null,
        public readonly mixed $child_service_condition = null,
        public readonly mixed $max_children_covered = null,
        public readonly mixed $max_infants_covered = null,
        public readonly mixed $infant_pricing_type = null,
        public readonly mixed $infant_pricing_value = null,
        public readonly mixed $child_pricing_type = null,
        public readonly mixed $child_pricing_value = null,
        public readonly mixed $description = null,
        public readonly mixed $status = null
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            accommodation_id: $request->input('accommodation_id'),
            max_infant_age: $request->input('max_infant_age'),
            max_child_age: $request->input('max_child_age'),
            infant_when_disabled: $request->input('infant_when_disabled'),
            child_when_disabled: $request->input('child_when_disabled'),
            infant_service_condition: $request->input('infant_service_condition'),
            child_service_condition: $request->input('child_service_condition'),
            max_children_covered: $request->input('max_children_covered'),
            max_infants_covered: $request->input('max_infants_covered'),
            infant_pricing_type: $request->input('infant_pricing_type'),
            infant_pricing_value: $request->input('infant_pricing_value'),
            child_pricing_type: $request->input('child_pricing_type'),
            child_pricing_value: $request->input('child_pricing_value'),
            description: $request->input('description'),
            status: $request->input('status'),
        );
    }

    public function toArray(): array
    {
        $out = [];
        if ($this->accommodation_id !== null) { $out['accommodation_id'] = $this->accommodation_id; }
        if ($this->max_infant_age !== null) { $out['max_infant_age'] = $this->max_infant_age; }
        if ($this->max_child_age !== null) { $out['max_child_age'] = $this->max_child_age; }
        if ($this->infant_when_disabled !== null) { $out['infant_when_disabled'] = $this->infant_when_disabled; }
        if ($this->child_when_disabled !== null) { $out['child_when_disabled'] = $this->child_when_disabled; }
        if ($this->infant_service_condition !== null) { $out['infant_service_condition'] = $this->infant_service_condition; }
        if ($this->child_service_condition !== null) { $out['child_service_condition'] = $this->child_service_condition; }
        if ($this->max_children_covered !== null) { $out['max_children_covered'] = $this->max_children_covered; }
        if ($this->max_infants_covered !== null) { $out['max_infants_covered'] = $this->max_infants_covered; }
        if ($this->infant_pricing_type !== null) { $out['infant_pricing_type'] = $this->infant_pricing_type; }
        if ($this->infant_pricing_value !== null) { $out['infant_pricing_value'] = $this->infant_pricing_value; }
        if ($this->child_pricing_type !== null) { $out['child_pricing_type'] = $this->child_pricing_type; }
        if ($this->child_pricing_value !== null) { $out['child_pricing_value'] = $this->child_pricing_value; }
        if ($this->description !== null) { $out['description'] = $this->description; }
        if ($this->status !== null) { $out['status'] = $this->status; }
        return $out;
    }
}
