<?php

namespace App\DTOs;

use Illuminate\Http\Request;

class CityDTO
{
    public mixed $id;
    public mixed $country_id;
    public mixed $state_id;
    public mixed $fa_name;
    public mixed $en_name;
    public mixed $lat;
    public mixed $lng;
    public mixed $osm_id;
    public mixed $is_popular;
    public mixed $is_active;
    public mixed $created_at;
    public mixed $updated_at;

    public function __construct(
        mixed $id = null,
        mixed $country_id = null,
        mixed $state_id = null,
        mixed $fa_name = null,
        mixed $en_name = null,
        mixed $lat = null,
        mixed $lng = null,
        mixed $osm_id = null,
        mixed $is_popular = null,
        mixed $is_active = null,
        mixed $created_at = null,
        mixed $updated_at = null
    ) {
        $this->id = $id;
        $this->country_id = $country_id;
        $this->state_id = $state_id;
        $this->fa_name = $fa_name;
        $this->en_name = $en_name;
        $this->lat = $lat;
        $this->lng = $lng;
        $this->osm_id = $osm_id;
        $this->is_popular = $is_popular;
        $this->is_active = $is_active;
        $this->created_at = $created_at;
        $this->updated_at = $updated_at;
    }

    public static function fromRequest(Request $request): self
    {
        $dto = new self();
        $dto->id = $request->input('id');
        $dto->country_id = $request->input('country_id');
        $dto->state_id = $request->input('state_id');
        $dto->fa_name = $request->input('fa_name');
        $dto->en_name = $request->input('en_name');
        $dto->lat = $request->input('lat');
        $dto->lng = $request->input('lng');
        $dto->osm_id = $request->input('osm_id');
        $dto->is_popular = $request->input('is_popular');
        $dto->is_active = $request->input('is_active');
        $dto->created_at = $request->input('created_at');
        $dto->updated_at = $request->input('updated_at');

        return $dto;
    }

    public function toArray(): array
    {
        $out = [];
        if ($this->id !== null) { $out['id'] = $this->id; }
        if ($this->country_id !== null) { $out['country_id'] = $this->country_id; }
        if ($this->state_id !== null) { $out['state_id'] = $this->state_id; }
        if ($this->fa_name !== null) { $out['fa_name'] = $this->fa_name; }
        if ($this->en_name !== null) { $out['en_name'] = $this->en_name; }
        if ($this->lat !== null) { $out['lat'] = $this->lat; }
        if ($this->lng !== null) { $out['lng'] = $this->lng; }
        if ($this->osm_id !== null) { $out['osm_id'] = $this->osm_id; }
        if ($this->is_popular !== null) { $out['is_popular'] = $this->is_popular; }
        if ($this->is_active !== null) { $out['is_active'] = $this->is_active; }
        if ($this->created_at !== null) { $out['created_at'] = $this->created_at; }
        if ($this->updated_at !== null) { $out['updated_at'] = $this->updated_at; }

        return $out;
    }
}
