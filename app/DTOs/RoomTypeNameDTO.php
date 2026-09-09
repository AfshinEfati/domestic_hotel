<?php

namespace App\DTOs;

use Illuminate\Http\Request;

class RoomTypeNameDTO
{
    public function __construct(
        public readonly mixed $fa_name = null,
        public readonly mixed $en_name = null
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            fa_name: $request->input('fa_name'),
            en_name: $request->input('en_name'),
        );
    }

    public function toArray(): array
    {
        $out = [];
        if ($this->fa_name !== null) { $out['fa_name'] = $this->fa_name; }
        if ($this->en_name !== null) { $out['en_name'] = $this->en_name; }
        return $out;
    }
}
