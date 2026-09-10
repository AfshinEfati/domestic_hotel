<?php

namespace App\Models;

use App\Support\Reservation\ReservationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Reservation extends Model
{
    protected $fillable = [
        'reservation_number',
        'status',
        'total_price',
        'email',
        'mobile',
    ];

    protected $casts = [
        'status' => 'integer',
        'total_price' => 'integer',
    ];

    protected $attributes = [
        'status' => ReservationStatus::REQUESTED,
    ];

    public function hotels(): HasMany
    {
        return $this->hasMany(ReservationHotel::class);
    }

    public function finalHotel(): HasOne
    {
        return $this->hasOne(ReservationHotel::class)->where('is_final', true);
    }
}
