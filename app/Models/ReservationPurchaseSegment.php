<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Represents the portion of a reservation room fulfilled by a purchase.
 */
class ReservationPurchaseSegment extends Model
{
    protected $fillable = [
        'reservation_purchase_id',
        'reservation_room_id',
        'from_date',
        'to_date',
        'nightly_purchase_amount',
        'nightly_extra_bed_purchase_amount',
        'nightly_child_purchase_amount',
        'nightly_infant_purchase_amount',
    ];

    protected $casts = [
        'reservation_purchase_id' => 'integer',
        'reservation_room_id' => 'integer',
        'from_date' => 'date:Y-m-d',
        'to_date' => 'date:Y-m-d',
        'nightly_purchase_amount' => 'integer',
        'nightly_extra_bed_purchase_amount' => 'integer',
        'nightly_child_purchase_amount' => 'integer',
        'nightly_infant_purchase_amount' => 'integer',
    ];

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(ReservationPurchase::class, 'reservation_purchase_id');
    }

    public function reservationRoom(): BelongsTo
    {
        return $this->belongsTo(ReservationRoom::class);
    }
}
