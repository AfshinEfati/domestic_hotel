<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** The SSP application owns this table and its migrations. */
class HotelPriceRefreshSchedule extends Model
{
    protected $connection = 'shared_ssp';

    protected $table = 'hotel_price_refresh_schedules';

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'gds_id' => 'integer',
        'is_active' => 'boolean',
        'refresh_interval_minutes' => 'integer',
        'next_gds_run_at' => 'datetime',
        'last_gds_success_run_at' => 'datetime',
        'last_gds_success_at' => 'datetime',
    ];
}
