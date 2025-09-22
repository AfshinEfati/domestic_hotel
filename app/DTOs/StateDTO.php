<?php

namespace App\DTOs;

use Illuminate\Http\Request;

class StateDTO
{
    public mixed $id;
    public mixed $country_id;
    public mixed $fa_name;
    public mixed $en_name;
    public mixed $created_at;
    public mixed $updated_at;

    public function __construct(
        mixed $id = null,
        mixed $country_id = null,
        mixed $fa_name = null,
        mixed $en_name = null,
        mixed $created_at = null,
        mixed $updated_at = null
    ) {
        $this->id = $id;
        $this->country_id = $country_id;
        $this->fa_name = $fa_name;
        $this->en_name = $en_name;
        $this->created_at = $created_at;
        $this->updated_at = $updated_at;
    }

    public static function fromRequest(Request $request): self
    {
        $dto = new self();
        $dto->id = $request->input('id');
        $dto->country_id = $request->input('country_id');
        $dto->fa_name = $request->input('fa_name');
        $dto->en_name = $request->input('en_name');
        $dto->created_at = $request->input('created_at');
        $dto->updated_at = $request->input('updated_at');

        return $dto;
    }

    public function toArray(): array
    {
        $out = [];
        if ($this->id !== null) { $out['id'] = $this->id; }
        if ($this->country_id !== null) { $out['country_id'] = $this->country_id; }
        if ($this->fa_name !== null) { $out['fa_name'] = $this->fa_name; }
        if ($this->en_name !== null) { $out['en_name'] = $this->en_name; }
        if ($this->created_at !== null) { $out['created_at'] = $this->created_at; }
        if ($this->updated_at !== null) { $out['updated_at'] = $this->updated_at; }

        return $out;
    }
}
