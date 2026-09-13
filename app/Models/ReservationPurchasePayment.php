<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Represents a payment made for fulfilling a reservation purchase.
 */
class ReservationPurchasePayment extends Model
{
    protected $fillable = [
        'reservation_purchase_id',
        'amount',
        'source',
        'bank_account_id',
        'card_id',
        'paid_at',
        'reference',
        'receipt_document_id',
        'status',
        'description',
    ];

    protected $casts = [
        'amount' => 'integer',
        'source' => 'integer',
        'status' => 'integer',
        'bank_account_id' => 'integer',
        'card_id' => 'integer',
        'paid_at' => 'datetime',
    ];

    public function reservationPurchase(): BelongsTo
    {
        return $this->belongsTo(ReservationPurchase::class);
    }
}
