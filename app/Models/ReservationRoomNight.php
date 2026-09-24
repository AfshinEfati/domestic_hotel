<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A single stay-date price row for a reservation room; its calendar ID remains a snapshot after pruning. */
class ReservationRoomNight extends Model
{
    protected $fillable = [
        'reservation_room_id',
        'date',
        'room_calendar_id',
        'initial_price',
        'validated_price',
    ];

    protected $casts = [
        'reservation_room_id' => 'integer',
        'date' => 'date:Y-m-d',
        'room_calendar_id' => 'integer',
        'initial_price' => 'integer',
        'validated_price' => 'integer',
    ];

    public function reservationRoom(): BelongsTo
    {
        return $this->belongsTo(ReservationRoom::class);
    }
}
