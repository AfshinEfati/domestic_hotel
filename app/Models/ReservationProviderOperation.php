<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Stores provider request timeline for reservation purchases.
 */
class ReservationProviderOperation extends Model
{
    protected $fillable = [
        'reservation_purchase_id',
        'operation',
        'status',
        'attempt',
        'handler_class',
        'handler_method',
        'url',
        'request_headers',
        'request_body',
        'http_status',
        'response_headers',
        'response_body',
        'idempotency_key',
        'exception_class',
        'error_message',
        'error_line',
        'started_at',
        'finished_at',
        'duration_ms',
    ];

    protected $casts = [
        'request_headers' => 'array',
        'response_headers' => 'array',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function reservationPurchase(): BelongsTo
    {
        return $this->belongsTo(ReservationPurchase::class);
    }
}
