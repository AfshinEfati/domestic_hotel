<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProviderCancellation extends Model
{
    protected $fillable = [
        'provider_id',
        'reservation_purchase_id',
        'tracking_code',
        'provider_cancellation_id',
        'status',
        'manual',
        'service_fee',
        'user_penalty',
        'user_penalty_percent',
        'user_penalty_total',
        'user_refund_amount',
        'provider_rules',
        'requested_at',
        'last_inquired_at',
        'decided_at',
        'finalized_at',
    ];

    protected $casts = [
        'provider_id' => 'integer',
        'reservation_purchase_id' => 'integer',
        'manual' => 'boolean',
        'service_fee' => 'integer',
        'user_penalty' => 'integer',
        'user_penalty_percent' => 'integer',
        'user_penalty_total' => 'integer',
        'user_refund_amount' => 'integer',
        'provider_rules' => 'array',
        'requested_at' => 'datetime',
        'last_inquired_at' => 'datetime',
        'decided_at' => 'datetime',
        'finalized_at' => 'datetime',
    ];

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function reservationPurchase(): BelongsTo
    {
        return $this->belongsTo(ReservationPurchase::class);
    }
}
