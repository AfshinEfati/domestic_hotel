<?php

namespace App\DTOs;

use Illuminate\Http\Request;

class RuleDTO
{
    public function __construct(
        public readonly mixed $title = null,
        public readonly mixed $name = null,
        public readonly mixed $is_active = null
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            title: $request->input('title'),
            name: $request->input('name'),
            is_active: $request->input('is_active'),
        );
    }

    public function toArray(): array
    {
        $out = [];
        if ($this->title !== null) { $out['title'] = $this->title; }
        if ($this->name !== null) { $out['name'] = $this->name; }
        if ($this->is_active !== null) { $out['is_active'] = $this->is_active; }
        return $out;
    }
}
