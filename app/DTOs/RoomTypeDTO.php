<?php

namespace App\DTOs;

use Illuminate\Http\Request;

class RoomTypeDTO
{
    public mixed $id;
    public mixed $accommodation_id;
    public mixed $fa_name;
    public mixed $en_name;
    public mixed $capacity;
    public mixed $extra_capacity;
    public mixed $single_bed_count;
    public mixed $double_bed_count;
    public mixed $sofa_bed_count;
    public mixed $out_of_service;
    public mixed $created_at;
    public mixed $updated_at;

    public function __construct(
        mixed $id = null,
        mixed $accommodation_id = null,
        mixed $fa_name = null,
        mixed $en_name = null,
        mixed $capacity = null,
        mixed $extra_capacity = null,
        mixed $single_bed_count = null,
        mixed $double_bed_count = null,
        mixed $sofa_bed_count = null,
        mixed $out_of_service = null,
        mixed $created_at = null,
        mixed $updated_at = null
    ) {
        $this->id = $id;
        $this->accommodation_id = $accommodation_id;
        $this->fa_name = $fa_name;
        $this->en_name = $en_name;
        $this->capacity = $capacity;
        $this->extra_capacity = $extra_capacity;
        $this->single_bed_count = $single_bed_count;
        $this->double_bed_count = $double_bed_count;
        $this->sofa_bed_count = $sofa_bed_count;
        $this->out_of_service = $out_of_service;
        $this->created_at = $created_at;
        $this->updated_at = $updated_at;
    }

    public static function fromRequest(Request $request): self
    {
        $dto = new self();
        $dto->id = $request->input('id');
        $dto->accommodation_id = $request->input('accommodation_id');
        $dto->fa_name = $request->input('fa_name');
        $dto->en_name = $request->input('en_name');
        $dto->capacity = $request->input('capacity');
        $dto->extra_capacity = $request->input('extra_capacity');
        $dto->single_bed_count = $request->input('single_bed_count');
        $dto->double_bed_count = $request->input('double_bed_count');
        $dto->sofa_bed_count = $request->input('sofa_bed_count');
        $dto->out_of_service = $request->input('out_of_service');
        $dto->created_at = $request->input('created_at');
        $dto->updated_at = $request->input('updated_at');

        return $dto;
    }

    public function toArray(): array
    {
        $out = [];
        if ($this->id !== null) { $out['id'] = $this->id; }
        if ($this->accommodation_id !== null) { $out['accommodation_id'] = $this->accommodation_id; }
        if ($this->fa_name !== null) { $out['fa_name'] = $this->fa_name; }
        if ($this->en_name !== null) { $out['en_name'] = $this->en_name; }
        if ($this->capacity !== null) { $out['capacity'] = $this->capacity; }
        if ($this->extra_capacity !== null) { $out['extra_capacity'] = $this->extra_capacity; }
        if ($this->single_bed_count !== null) { $out['single_bed_count'] = $this->single_bed_count; }
        if ($this->double_bed_count !== null) { $out['double_bed_count'] = $this->double_bed_count; }
        if ($this->sofa_bed_count !== null) { $out['sofa_bed_count'] = $this->sofa_bed_count; }
        if ($this->out_of_service !== null) { $out['out_of_service'] = $this->out_of_service; }
        if ($this->created_at !== null) { $out['created_at'] = $this->created_at; }
        if ($this->updated_at !== null) { $out['updated_at'] = $this->updated_at; }

        return $out;
    }
}
