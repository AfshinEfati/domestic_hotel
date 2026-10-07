<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HotelProviderRefreshState extends Model
{
    protected $fillable = [
        'shared_schedule_id',
        'accommodation_id',
        'provider_id',
        'accommodation_provider_map_id',
        'cycle_key',
        'source_due_at',
        'status',
        'outcome',
        'attempts',
        'next_attempt_at',
        'queued_at',
        'started_at',
        'last_attempt_at',
        'last_success_at',
        'completed_at',
        'lease_expires_at',
    ];

    protected $casts = [
        'shared_schedule_id' => 'integer',
        'accommodation_id' => 'integer',
        'provider_id' => 'integer',
        'accommodation_provider_map_id' => 'integer',
        'status' => 'integer',
        'attempts' => 'integer',
        'source_due_at' => 'datetime',
        'next_attempt_at' => 'datetime',
        'queued_at' => 'datetime',
        'started_at' => 'datetime',
        'last_attempt_at' => 'datetime',
        'last_success_at' => 'datetime',
        'completed_at' => 'datetime',
        'lease_expires_at' => 'datetime',
    ];

    public function accommodation(): BelongsTo
    {
        return $this->belongsTo(Accommodation::class);
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function accommodationProviderMap(): BelongsTo
    {
        return $this->belongsTo(AccommodationProviderMap::class);
    }
}
