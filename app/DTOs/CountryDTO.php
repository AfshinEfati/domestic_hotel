<?php

namespace App\DTOs;

use Illuminate\Http\Request;

class CountryDTO
{
    public mixed $id;
    public mixed $fa_name;
    public mixed $en_name;
    public mixed $iso2;
    public mixed $iso3;
    public mixed $created_at;
    public mixed $updated_at;

    public function __construct(
        mixed $id = null,
        mixed $fa_name = null,
        mixed $en_name = null,
        mixed $iso2 = null,
        mixed $iso3 = null,
        mixed $created_at = null,
        mixed $updated_at = null
    ) {
        $this->id = $id;
        $this->fa_name = $fa_name;
        $this->en_name = $en_name;
        $this->iso2 = $iso2;
        $this->iso3 = $iso3;
        $this->created_at = $created_at;
        $this->updated_at = $updated_at;
    }

    public static function fromRequest(Request $request): self
    {
        $dto = new self();
        $dto->id = $request->input('id');
        $dto->fa_name = $request->input('fa_name');
        $dto->en_name = $request->input('en_name');
        $dto->iso2 = $request->input('iso2');
        $dto->iso3 = $request->input('iso3');
        $dto->created_at = $request->input('created_at');
        $dto->updated_at = $request->input('updated_at');

        return $dto;
    }

    public function toArray(): array
    {
        $out = [];
        if ($this->id !== null) { $out['id'] = $this->id; }
        if ($this->fa_name !== null) { $out['fa_name'] = $this->fa_name; }
        if ($this->en_name !== null) { $out['en_name'] = $this->en_name; }
        if ($this->iso2 !== null) { $out['iso2'] = $this->iso2; }
        if ($this->iso3 !== null) { $out['iso3'] = $this->iso3; }
        if ($this->created_at !== null) { $out['created_at'] = $this->created_at; }
        if ($this->updated_at !== null) { $out['updated_at'] = $this->updated_at; }

        return $out;
    }
}
