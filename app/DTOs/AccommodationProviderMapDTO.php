<?php

namespace App\DTOs;

use Illuminate\Http\Request;

class AccommodationProviderMapDTO
{
    public mixed $id;
    public mixed $accommodation_id;
    public mixed $provider_id;
    public mixed $provider_property_id;
    public mixed $fa_name;
    public mixed $en_name;
    public mixed $created_at;
    public mixed $updated_at;

    public function __construct(
        mixed $id = null,
        mixed $accommodation_id = null,
        mixed $provider_id = null,
        mixed $provider_property_id = null,
        mixed $fa_name = null,
        mixed $en_name = null,
        mixed $created_at = null,
        mixed $updated_at = null
    ) {
        $this->id = $id;
        $this->accommodation_id = $accommodation_id;
        $this->provider_id = $provider_id;
        $this->provider_property_id = $provider_property_id;
        $this->fa_name = $fa_name;
        $this->en_name = $en_name;
        $this->created_at = $created_at;
        $this->updated_at = $updated_at;
    }

    public static function fromRequest(Request $request): self
    {
        $dto = new self();
        $dto->id = $request->input('id');
        $dto->accommodation_id = $request->input('accommodation_id');
        $dto->provider_id = $request->input('provider_id');
        $dto->provider_property_id = $request->input('provider_property_id');
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
        if ($this->accommodation_id !== null) { $out['accommodation_id'] = $this->accommodation_id; }
        if ($this->provider_id !== null) { $out['provider_id'] = $this->provider_id; }
        if ($this->provider_property_id !== null) { $out['provider_property_id'] = $this->provider_property_id; }
        if ($this->fa_name !== null) { $out['fa_name'] = $this->fa_name; }
        if ($this->en_name !== null) { $out['en_name'] = $this->en_name; }
        if ($this->created_at !== null) { $out['created_at'] = $this->created_at; }
        if ($this->updated_at !== null) { $out['updated_at'] = $this->updated_at; }

        return $out;
    }
}
