<?php

namespace App\Models;

use App\Support\Reservation\ReservationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Represents a procurement purchase used to fulfill a reservation hotel.
 */
class ReservationPurchase extends Model
{
    protected $fillable = [
        'reservation_hotel_id',
        'provider_id',
        'status',
        'quoted_provider_id',
        'provider_quoted_amount',
        'purchase_amount',
        'confirmation_code',
        'provider_status',
        'expires_at',
        'issued_at',
    ];

    protected $casts = [
        'reservation_hotel_id' => 'integer',
        'provider_id' => 'integer',
        'status' => 'integer',
        'quoted_provider_id' => 'integer',
        'provider_quoted_amount' => 'integer',
        'purchase_amount' => 'integer',
        'expires_at' => 'datetime',
        'issued_at' => 'datetime',
    ];

    protected $attributes = [
        'status' => ReservationStatus::PURCHASE_QUEUED,
    ];

    public function reservationHotel(): BelongsTo
    {
        return $this->belongsTo(ReservationHotel::class);
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function quotedProvider(): BelongsTo
    {
        return $this->belongsTo(Provider::class, 'quoted_provider_id');
    }

    public function segments(): HasMany
    {
        return $this->hasMany(ReservationPurchaseSegment::class);
    }
}
