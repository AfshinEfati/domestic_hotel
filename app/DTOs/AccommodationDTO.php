<?php

namespace App\DTOs;

use Illuminate\Http\Request;

class AccommodationDTO
{
    public mixed $id;
    public mixed $city_id;
    public mixed $fa_name;
    public mixed $en_name;
    public mixed $accommodation_type_id;
    public mixed $star;
    public mixed $grade;
    public mixed $address;
    public mixed $lat;
    public mixed $lng;
    public mixed $is_active;
    public mixed $created_at;
    public mixed $updated_at;

    public function __construct(
        mixed $id = null,
        mixed $city_id = null,
        mixed $fa_name = null,
        mixed $en_name = null,
        mixed $accommodation_type_id = null,
        mixed $star = null,
        mixed $grade = null,
        mixed $address = null,
        mixed $lat = null,
        mixed $lng = null,
        mixed $is_active = null,
        mixed $created_at = null,
        mixed $updated_at = null
    ) {
        $this->id = $id;
        $this->city_id = $city_id;
        $this->fa_name = $fa_name;
        $this->en_name = $en_name;
        $this->accommodation_type_id = $accommodation_type_id;
        $this->star = $star;
        $this->grade = $grade;
        $this->address = $address;
        $this->lat = $lat;
        $this->lng = $lng;
        $this->is_active = $is_active;
        $this->created_at = $created_at;
        $this->updated_at = $updated_at;
    }

    public static function fromRequest(Request $request): self
    {
        $dto = new self();
            $dto->id = $request->input('id');
            $dto->city_id = $request->input('city_id');
            $dto->fa_name = $request->input('fa_name');
            $dto->en_name = $request->input('en_name');
            $dto->accommodation_type_id = $request->input('accommodation_type_id');
            $dto->star = $request->input('star');
            $dto->grade = $request->input('grade');
            $dto->address = $request->input('address');
            $dto->lat = $request->input('lat');
            $dto->lng = $request->input('lng');
            $dto->is_active = $request->input('is_active');
            $dto->created_at = $request->input('created_at');
            $dto->updated_at = $request->input('updated_at');
        return $dto;
    }

    public function toArray(): array
    {
        $out = [];
        if ($this->id !== null) { $out['id'] = $this->id; }
        if ($this->city_id !== null) { $out['city_id'] = $this->city_id; }
        if ($this->fa_name !== null) { $out['fa_name'] = $this->fa_name; }
        if ($this->en_name !== null) { $out['en_name'] = $this->en_name; }
        if ($this->accommodation_type_id !== null) { $out['accommodation_type_id'] = $this->accommodation_type_id; }
        if ($this->star !== null) { $out['star'] = $this->star; }
        if ($this->grade !== null) { $out['grade'] = $this->grade; }
        if ($this->address !== null) { $out['address'] = $this->address; }
        if ($this->lat !== null) { $out['lat'] = $this->lat; }
        if ($this->lng !== null) { $out['lng'] = $this->lng; }
        if ($this->is_active !== null) { $out['is_active'] = $this->is_active; }
        if ($this->created_at !== null) { $out['created_at'] = $this->created_at; }
        if ($this->updated_at !== null) { $out['updated_at'] = $this->updated_at; }
        return $out;
    }
}
