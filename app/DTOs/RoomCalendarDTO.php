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
    public mixed $child_daily_rate;
    public mixed $infant_daily_rate;
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
        mixed $child_daily_rate = null,
        mixed $infant_daily_rate = null,
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
        foreach (get_defined_vars() as $key => $value) {
            $this->{$key} = $value;
        }
    }

    public static function fromRequest(Request $request): self
    {
        $dto = new self();
        foreach ([
            'id', 'accommodation_id', 'room_type_id', 'rate_plan_id', 'day',
            'rack_rate', 'daily_rate', 'grs_rate',
            'baby_cot_rack_rate', 'baby_cot_daily_rate', 'baby_cot_grs_rate',
            'child_daily_rate', 'infant_daily_rate',
            'extend_bed_rack_rate', 'extend_bed_daily_rate', 'extend_bed_grs_rate',
            'min_stay', 'max_stay', 'cta', 'ctd', 'closed', 'inventory',
            'provider_id', 'provider_property_id', 'provider_room_type_id', 'provider_rate_plan_id',
            'created_at', 'updated_at',
        ] as $field) {
            $dto->{$field} = $request->input($field);
        }

        return $dto;
    }

    public function toArray(): array
    {
        $out = [];
        foreach (get_object_vars($this) as $field => $value) {
            if ($value !== null) {
                $out[$field] = $value;
            }
        }

        return $out;
    }
}
