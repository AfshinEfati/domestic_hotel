<?php

namespace App\DTOs;

use Illuminate\Http\Request;

class SystemSettingDTO
{
    public function __construct(
        public readonly mixed $key = null,
        public readonly mixed $group = null,
        public readonly mixed $value = null,
        public readonly mixed $value_type = null,
        public readonly mixed $is_active = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            key: $request->input('key'),
            group: $request->input('group'),
            value: $request->input('value'),
            value_type: $request->input('value_type'),
            is_active: $request->input('is_active'),
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'key' => $this->key,
            'group' => $this->group,
            'value' => $this->value,
            'value_type' => $this->value_type,
            'is_active' => $this->is_active,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
