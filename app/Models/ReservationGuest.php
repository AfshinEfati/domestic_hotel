<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Represents a guest assigned to a specific reserved room.
 */
class ReservationGuest extends Model
{
    protected $fillable = [
        'reservation_room_id',
        'type',
        'first_name',
        'last_name',
        'gender',
        'birth_date',
        'country_id',
        'national_id',
        'passport_number',
        'passport_issuer_country_id',
        'passport_expiry_date',
    ];

    protected $casts = [
        'reservation_room_id' => 'integer',
        'type' => 'integer',
        'gender' => 'integer',
        'birth_date' => 'date:Y-m-d',
        'country_id' => 'integer',
        'passport_issuer_country_id' => 'integer',
        'passport_expiry_date' => 'date:Y-m-d',
    ];

    public function room(): BelongsTo
    {
        return $this->belongsTo(ReservationRoom::class, 'reservation_room_id');
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function passportIssuerCountry(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'passport_issuer_country_id');
    }
}
