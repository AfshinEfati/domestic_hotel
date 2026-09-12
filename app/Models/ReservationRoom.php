<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Represents a single physical room assigned to a reservation hotel.
 */
class ReservationRoom extends Model
{
    protected $fillable = [
        'reservation_hotel_id',
        'room_number',
        'room_type_id',
        'rate_plan_id',
        'room_name',
    ];

    protected $casts = [
        'reservation_hotel_id' => 'integer',
        'room_number' => 'integer',
        'room_type_id' => 'integer',
        'rate_plan_id' => 'integer',
    ];

    public function reservationHotel(): BelongsTo
    {
        return $this->belongsTo(ReservationHotel::class);
    }

    public function roomType(): BelongsTo
    {
        return $this->belongsTo(RoomType::class);
    }

    public function ratePlan(): BelongsTo
    {
        return $this->belongsTo(RatePlan::class);
    }

    public function guests(): HasMany
    {
        return $this->hasMany(ReservationGuest::class);
    }

    public function purchaseSegments(): HasMany
    {
        return $this->hasMany(ReservationPurchaseSegment::class);
    }
}
