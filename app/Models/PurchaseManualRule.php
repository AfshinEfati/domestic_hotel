<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseManualRule extends Model
{
    protected $fillable = [
        'name',
        'provider_id',
        'accommodation_id',
        'minimum_amount',
        'maximum_amount',
        'start_time',
        'end_time',
        'is_active',
    ];

    protected $casts = [
        'minimum_amount' => 'integer',
        'maximum_amount' => 'integer',
        'is_active' => 'boolean',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
    ];

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function accommodation(): BelongsTo
    {
        return $this->belongsTo(Accommodation::class);
    }
}
