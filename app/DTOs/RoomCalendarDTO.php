<?php

namespace App\DTOs;

use Illuminate\Http\Request;

class RoomCalendarDTO
{
    public mixed $id;
    public mixed $accommodation_id;
    public mixed $room_type_id;
    public mixed $rate_plan_id;
    public mixed $day;
    public mixed $rack_rate;
    public mixed $daily_rate;
    public mixed $grs_rate;
    public mixed $baby_cot_rack_rate;
    public mixed $baby_cot_daily_rate;
    public mixed $baby_cot_grs_rate;
    public mixed $extend_bed_rack_rate;
    public mixed $extend_bed_daily_rate;
    public mixed $extend_bed_grs_rate;
    public mixed $min_stay;
    public mixed $max_stay;
    public mixed $cta;
    public mixed $ctd;
    public mixed $closed;
    public mixed $inventory;
    public mixed $provider_id;
    public mixed $provider_property_id;
    public mixed $provider_room_type_id;
    public mixed $provider_rate_plan_id;
    public mixed $created_at;
    public mixed $updated_at;

    public function __construct(
        mixed $id = null,
        mixed $accommodation_id = null,
        mixed $room_type_id = null,
        mixed $rate_plan_id = null,
        mixed $day = null,
        mixed $rack_rate = null,
        mixed $daily_rate = null,
        mixed $grs_rate = null,
        mixed $baby_cot_rack_rate = null,
        mixed $baby_cot_daily_rate = null,
        mixed $baby_cot_grs_rate = null,
        mixed $extend_bed_rack_rate = null,
        mixed $extend_bed_daily_rate = null,
        mixed $extend_bed_grs_rate = null,
        mixed $min_stay = null,
        mixed $max_stay = null,
        mixed $cta = null,
        mixed $ctd = null,
        mixed $closed = null,
        mixed $inventory = null,
        mixed $provider_id = null,
        mixed $provider_property_id = null,
        mixed $provider_room_type_id = null,
        mixed $provider_rate_plan_id = null,
        mixed $created_at = null,
        mixed $updated_at = null
    ) {
        $this->id = $id;
        $this->accommodation_id = $accommodation_id;
        $this->room_type_id = $room_type_id;
        $this->rate_plan_id = $rate_plan_id;
        $this->day = $day;
        $this->rack_rate = $rack_rate;
        $this->daily_rate = $daily_rate;
        $this->grs_rate = $grs_rate;
        $this->baby_cot_rack_rate = $baby_cot_rack_rate;
        $this->baby_cot_daily_rate = $baby_cot_daily_rate;
        $this->baby_cot_grs_rate = $baby_cot_grs_rate;
        $this->extend_bed_rack_rate = $extend_bed_rack_rate;
        $this->extend_bed_daily_rate = $extend_bed_daily_rate;
        $this->extend_bed_grs_rate = $extend_bed_grs_rate;
        $this->min_stay = $min_stay;
        $this->max_stay = $max_stay;
        $this->cta = $cta;
        $this->ctd = $ctd;
        $this->closed = $closed;
        $this->inventory = $inventory;
        $this->provider_id = $provider_id;
        $this->provider_property_id = $provider_property_id;
        $this->provider_room_type_id = $provider_room_type_id;
        $this->provider_rate_plan_id = $provider_rate_plan_id;
        $this->created_at = $created_at;
        $this->updated_at = $updated_at;
    }

    public static function fromRequest(Request $request): self
    {
        $dto = new self();
        $dto->id = $request->input('id');
        $dto->accommodation_id = $request->input('accommodation_id');
        $dto->room_type_id = $request->input('room_type_id');
        $dto->rate_plan_id = $request->input('rate_plan_id');
        $dto->day = $request->input('day');
        $dto->rack_rate = $request->input('rack_rate');
        $dto->daily_rate = $request->input('daily_rate');
        $dto->grs_rate = $request->input('grs_rate');
        $dto->baby_cot_rack_rate = $request->input('baby_cot_rack_rate');
        $dto->baby_cot_daily_rate = $request->input('baby_cot_daily_rate');
        $dto->baby_cot_grs_rate = $request->input('baby_cot_grs_rate');
        $dto->extend_bed_rack_rate = $request->input('extend_bed_rack_rate');
        $dto->extend_bed_daily_rate = $request->input('extend_bed_daily_rate');
        $dto->extend_bed_grs_rate = $request->input('extend_bed_grs_rate');
        $dto->min_stay = $request->input('min_stay');
        $dto->max_stay = $request->input('max_stay');
        $dto->cta = $request->input('cta');
        $dto->ctd = $request->input('ctd');
        $dto->closed = $request->input('closed');
        $dto->inventory = $request->input('inventory');
        $dto->provider_id = $request->input('provider_id');
        $dto->provider_property_id = $request->input('provider_property_id');
        $dto->provider_room_type_id = $request->input('provider_room_type_id');
        $dto->provider_rate_plan_id = $request->input('provider_rate_plan_id');
        $dto->created_at = $request->input('created_at');
        $dto->updated_at = $request->input('updated_at');

        return $dto;
    }

    public function toArray(): array
    {
        $out = [];
        if ($this->id !== null) { $out['id'] = $this->id; }
        if ($this->accommodation_id !== null) { $out['accommodation_id'] = $this->accommodation_id; }
        if ($this->room_type_id !== null) { $out['room_type_id'] = $this->room_type_id; }
        if ($this->rate_plan_id !== null) { $out['rate_plan_id'] = $this->rate_plan_id; }
        if ($this->day !== null) { $out['day'] = $this->day; }
        if ($this->rack_rate !== null) { $out['rack_rate'] = $this->rack_rate; }
        if ($this->daily_rate !== null) { $out['daily_rate'] = $this->daily_rate; }
        if ($this->grs_rate !== null) { $out['grs_rate'] = $this->grs_rate; }
        if ($this->baby_cot_rack_rate !== null) { $out['baby_cot_rack_rate'] = $this->baby_cot_rack_rate; }
        if ($this->baby_cot_daily_rate !== null) { $out['baby_cot_daily_rate'] = $this->baby_cot_daily_rate; }
        if ($this->baby_cot_grs_rate !== null) { $out['baby_cot_grs_rate'] = $this->baby_cot_grs_rate; }
        if ($this->extend_bed_rack_rate !== null) { $out['extend_bed_rack_rate'] = $this->extend_bed_rack_rate; }
        if ($this->extend_bed_daily_rate !== null) { $out['extend_bed_daily_rate'] = $this->extend_bed_daily_rate; }
        if ($this->extend_bed_grs_rate !== null) { $out['extend_bed_grs_rate'] = $this->extend_bed_grs_rate; }
        if ($this->min_stay !== null) { $out['min_stay'] = $this->min_stay; }
        if ($this->max_stay !== null) { $out['max_stay'] = $this->max_stay; }
        if ($this->cta !== null) { $out['cta'] = $this->cta; }
        if ($this->ctd !== null) { $out['ctd'] = $this->ctd; }
        if ($this->closed !== null) { $out['closed'] = $this->closed; }
        if ($this->inventory !== null) { $out['inventory'] = $this->inventory; }
        if ($this->provider_id !== null) { $out['provider_id'] = $this->provider_id; }
        if ($this->provider_property_id !== null) { $out['provider_property_id'] = $this->provider_property_id; }
        if ($this->provider_room_type_id !== null) { $out['provider_room_type_id'] = $this->provider_room_type_id; }
        if ($this->provider_rate_plan_id !== null) { $out['provider_rate_plan_id'] = $this->provider_rate_plan_id; }
        if ($this->created_at !== null) { $out['created_at'] = $this->created_at; }
        if ($this->updated_at !== null) { $out['updated_at'] = $this->updated_at; }

        return $out;
    }
}
