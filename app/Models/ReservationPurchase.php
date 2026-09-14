<?php

namespace App\Models;

use App\Support\Reservation\PurchaseMethod;
use App\Support\Reservation\ReservationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

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
        'purchase_mode',
        'manual_reason',
        'manual_rule_id',
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
        'purchase_mode' => 'integer',
        'manual_reason' => 'integer',
        'manual_rule_id' => 'integer',
    ];

    protected $attributes = [
        'status' => ReservationStatus::PURCHASE_QUEUED,
        'purchase_mode' => PurchaseMethod::ONLINE,
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

    public function manualRule(): BelongsTo
    {
        return $this->belongsTo(PurchaseManualRule::class, 'manual_rule_id');
    }

    public function manualPurchase(): HasOne
    {
        return $this->hasOne(ReservationManualPurchase::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(ReservationPurchasePayment::class);
    }

    public function providerOperations(): HasMany
    {
        return $this->hasMany(ReservationProviderOperation::class);
    }

    public function segments(): HasMany
    {
        return $this->hasMany(ReservationPurchaseSegment::class);
    }
}
