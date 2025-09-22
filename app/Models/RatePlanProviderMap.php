<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RatePlanProviderMap extends Model
{
    protected $fillable = [
        'id',
        'rate_plan_id',
        'provider_id',
        'provider_rate_plan_id',
        'fa_name',
        'en_name',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'id' => 'integer',
        'rate_plan_id' => 'integer',
        'provider_id' => 'integer',
        'provider_rate_plan_id' => 'string',
        'fa_name' => 'string',
        'en_name' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function ratePlan(): BelongsTo
    {
        return $this->belongsTo(RatePlan::class);
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }
}
