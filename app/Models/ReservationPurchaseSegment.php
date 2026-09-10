<?php

namespace App\Models;

use App\Support\Reservation\PurchaseMethod;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReservationPurchaseSegment extends Model
{
    protected $fillable = [
        'reservation_room_id',
        'provider_id',
        'purchase_method',
        'from_date',
        'to_date',
        'provider_reference',
        'provider_property_id',
        'provider_room_type_id',
        'provider_rate_plan_id',
        'purchased_at',
        'issued_at',
        'notes',
    ];

    protected $casts = [
        'reservation_room_id' => 'integer',
        'provider_id' => 'integer',
        'purchase_method' => 'integer',
        'from_date' => 'date',
        'to_date' => 'date',
        'purchased_at' => 'datetime',
        'issued_at' => 'datetime',
    ];

    protected $attributes = [
        'purchase_method' => PurchaseMethod::ONLINE,
    ];

    public function reservationRoom(): BelongsTo
    {
        return $this->belongsTo(ReservationRoom::class);
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }
}
