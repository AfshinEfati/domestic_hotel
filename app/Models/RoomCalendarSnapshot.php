<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoomCalendarSnapshot extends Model
{
    protected $fillable = [
        'id',
        'room_calendar_id',
        'provider_id',
        'day',
        'payload',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'id' => 'integer',
        'room_calendar_id' => 'integer',
        'provider_id' => 'integer',
        'day' => 'date',
        'payload' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function roomCalendar(): BelongsTo
    {
        return $this->belongsTo(RoomCalendar::class);
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }
}
