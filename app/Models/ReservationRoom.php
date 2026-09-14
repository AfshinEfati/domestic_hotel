<?php

namespace App\Models;

use App\Support\Reservation\ReservationRoomType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Represents a single room slot assigned to a reservation hotel. A slot may have
 * a requested and an alternative candidate; only one candidate per slot is final.
 */
class ReservationRoom extends Model
{
    protected $fillable = [
        'reservation_hotel_id',
        'room_number',
        'type',
        'is_final',
        'room_type_id',
        'rate_plan_id',
        'room_name',
    ];

    protected $casts = [
        'reservation_hotel_id' => 'integer',
        'room_number' => 'integer',
        'type' => 'integer',
        'is_final' => 'boolean',
        'room_type_id' => 'integer',
        'rate_plan_id' => 'integer',
    ];

    protected $attributes = [
        'type' => ReservationRoomType::REQUESTED,
        'is_final' => true,
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
