<?php

namespace App\DTOs;

use Illuminate\Http\Request;

class RatePlanDTO
{
    public mixed $id;
    public mixed $accommodation_id;
    public mixed $fa_name;
    public mixed $en_name;
    public mixed $meal_type;
    public mixed $food_board_type;
    public mixed $cancelable;
    public mixed $sleeps;
    public mixed $min_stay;
    public mixed $max_stay;
    public mixed $facilities;
    public mixed $created_at;
    public mixed $updated_at;

    public function __construct(
        mixed $id = null,
        mixed $accommodation_id = null,
        mixed $fa_name = null,
        mixed $en_name = null,
        mixed $meal_type = null,
        mixed $food_board_type = null,
        mixed $cancelable = null,
        mixed $sleeps = null,
        mixed $min_stay = null,
        mixed $max_stay = null,
        mixed $facilities = null,
        mixed $created_at = null,
        mixed $updated_at = null
    ) {
        $this->id = $id;
        $this->accommodation_id = $accommodation_id;
        $this->fa_name = $fa_name;
        $this->en_name = $en_name;
        $this->meal_type = $meal_type;
        $this->food_board_type = $food_board_type;
        $this->cancelable = $cancelable;
        $this->sleeps = $sleeps;
        $this->min_stay = $min_stay;
        $this->max_stay = $max_stay;
        $this->facilities = $facilities;
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
        $dto->meal_type = $request->input('meal_type');
        $dto->food_board_type = $request->input('food_board_type');
        $dto->cancelable = $request->input('cancelable');
        $dto->sleeps = $request->input('sleeps');
        $dto->min_stay = $request->input('min_stay');
        $dto->max_stay = $request->input('max_stay');
        $dto->facilities = $request->input('facilities');
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
        if ($this->meal_type !== null) { $out['meal_type'] = $this->meal_type; }
        if ($this->food_board_type !== null) { $out['food_board_type'] = $this->food_board_type; }
        if ($this->cancelable !== null) { $out['cancelable'] = $this->cancelable; }
        if ($this->sleeps !== null) { $out['sleeps'] = $this->sleeps; }
        if ($this->min_stay !== null) { $out['min_stay'] = $this->min_stay; }
        if ($this->max_stay !== null) { $out['max_stay'] = $this->max_stay; }
        if ($this->facilities !== null) { $out['facilities'] = $this->facilities; }
        if ($this->created_at !== null) { $out['created_at'] = $this->created_at; }
        if ($this->updated_at !== null) { $out['updated_at'] = $this->updated_at; }

        return $out;
    }
}
