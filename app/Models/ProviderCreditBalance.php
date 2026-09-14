<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Represents the last known credit balance of an online provider, synced periodically
 * from the provider's API. Only meaningful for providers where is_online = true.
 */
class ProviderCreditBalance extends Model
{
    protected $fillable = [
        'provider_id',
        'balance',
        'low_balance_threshold',
        'low_balance_notified_at',
        'synced_at',
    ];

    protected $casts = [
        'provider_id' => 'integer',
        'balance' => 'integer',
        'low_balance_threshold' => 'integer',
        'low_balance_notified_at' => 'datetime',
        'synced_at' => 'datetime',
    ];

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function isBelowThreshold(): bool
    {
        if ($this->low_balance_threshold === null) {
            return false;
        }

        return $this->balance < $this->low_balance_threshold;
    }
}
