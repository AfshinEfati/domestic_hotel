<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReservationRoom extends Model
{
    protected $fillable = [
        'reservation_hotel_id',
        'room_type_id',
        'rate_plan_id',
        'room_name',
        'quantity',
        'check_in',
        'check_out',
    ];

    protected $casts = [
        'reservation_hotel_id' => 'integer',
        'room_type_id' => 'integer',
        'rate_plan_id' => 'integer',
        'quantity' => 'integer',
        'check_in' => 'date',
        'check_out' => 'date',
    ];

    protected $attributes = [
        'quantity' => 1,
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

    public function purchaseSegments(): HasMany
    {
        return $this->hasMany(ReservationPurchaseSegment::class);
    }
}
