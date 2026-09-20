<?php

namespace App\Models;

use App\Support\Reservation\ReservationRoomType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** A room slot in a reservation; its calendar ID remains a snapshot after pruning. */
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
        'room_calendar_id',
        'provider_id',
        'initial_price',
        'validated_price',
    ];

    protected $casts = [
        'reservation_hotel_id' => 'integer',
        'room_number' => 'integer',
        'type' => 'integer',
        'is_final' => 'boolean',
        'room_type_id' => 'integer',
        'rate_plan_id' => 'integer',
        'room_calendar_id' => 'integer',
        'provider_id' => 'integer',
        'initial_price' => 'integer',
        'validated_price' => 'integer',
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
