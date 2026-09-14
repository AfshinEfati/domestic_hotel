<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Represents the manual execution details of a reservation purchase handled
 * step-by-step by a sales operator, as opposed to an automated online purchase.
 */
class ReservationManualPurchase extends Model
{
    protected $fillable = [
        'reservation_purchase_id',
        'acc_code',
        'purchased_at',
        'description',
    ];

    protected $casts = [
        'reservation_purchase_id' => 'integer',
        'purchased_at' => 'datetime',
    ];

    public function reservationPurchase(): BelongsTo
    {
        return $this->belongsTo(ReservationPurchase::class);
    }
}
