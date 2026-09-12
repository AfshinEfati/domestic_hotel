<?php

namespace App\Models;

use App\Support\Reservation\ReservationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Represents the core reservation record managed by the domestic hotel service.
 */
class Reservation extends Model
{
    protected $fillable = [
        'status',
        'check_in',
        'check_out',
        'sale_amount',
        'booker_first_name',
        'booker_last_name',
        'booker_mobile',
        'booker_email',
        'acc_code',
    ];

    protected $casts = [
        'status' => 'integer',
        'check_in' => 'date:Y-m-d',
        'check_out' => 'date:Y-m-d',
        'sale_amount' => 'integer',
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
