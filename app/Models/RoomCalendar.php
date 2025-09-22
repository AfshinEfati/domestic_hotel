<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoomCalendar extends Model
{
    protected $fillable = [
        'id',
        'accommodation_id',
        'room_type_id',
        'rate_plan_id',
        'day',
        'rack_rate',
        'daily_rate',
        'grs_rate',
        'baby_cot_rack_rate',
        'baby_cot_daily_rate',
        'baby_cot_grs_rate',
        'extend_bed_rack_rate',
        'extend_bed_daily_rate',
        'extend_bed_grs_rate',
        'min_stay',
        'max_stay',
        'cta',
        'ctd',
        'closed',
        'inventory',
        'provider_id',
        'provider_property_id',
        'provider_room_type_id',
        'provider_rate_plan_id',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'id' => 'integer',
        'accommodation_id' => 'integer',
        'room_type_id' => 'integer',
        'rate_plan_id' => 'integer',
        'day' => 'date',
        'rack_rate' => 'integer',
        'daily_rate' => 'integer',
        'grs_rate' => 'integer',
        'baby_cot_rack_rate' => 'integer',
        'baby_cot_daily_rate' => 'integer',
        'baby_cot_grs_rate' => 'integer',
        'extend_bed_rack_rate' => 'integer',
        'extend_bed_daily_rate' => 'integer',
        'extend_bed_grs_rate' => 'integer',
        'min_stay' => 'integer',
        'max_stay' => 'integer',
        'cta' => 'boolean',
        'ctd' => 'boolean',
        'closed' => 'boolean',
        'inventory' => 'integer',
        'provider_id' => 'integer',
        'provider_property_id' => 'string',
        'provider_room_type_id' => 'string',
        'provider_rate_plan_id' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function accommodation(): BelongsTo
    {
        return $this->belongsTo(Accommodation::class);
    }

    public function roomType(): BelongsTo
    {
        return $this->belongsTo(RoomType::class);
    }

    public function ratePlan(): BelongsTo
    {
        return $this->belongsTo(RatePlan::class);
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }
}
