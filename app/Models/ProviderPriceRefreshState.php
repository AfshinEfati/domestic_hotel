<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProviderPriceRefreshState extends Model
{
    protected $fillable = [
        'provider_id',
        'accommodation_id',
        'schedule_id',
        'next_run_at',
        'last_request_at',
        'last_success_at',
        'last_persisted_at',
        'last_error',
    ];

    protected $casts = [
        'provider_id' => 'integer',
        'accommodation_id' => 'integer',
        'schedule_id' => 'integer',
        'next_run_at' => 'datetime',
        'last_request_at' => 'datetime',
        'last_success_at' => 'datetime',
        'last_persisted_at' => 'datetime',
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
