<?php

namespace App\Models;

use App\Support\Reservation\ReservationHotelType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReservationHotel extends Model
{
    protected $fillable = [
        'reservation_id',
        'accommodation_id',
        'type',
        'is_final',
    ];

    protected $casts = [
        'reservation_id' => 'integer',
        'accommodation_id' => 'integer',
        'type' => 'integer',
        'is_final' => 'boolean',
    ];

    protected $attributes = [
        'type' => ReservationHotelType::REQUESTED,
        'is_final' => false,
    ];

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function accommodation(): BelongsTo
    {
        return $this->belongsTo(Accommodation::class);
    }

    public function rooms(): HasMany
    {
        return $this->hasMany(ReservationRoom::class);
    }
}
