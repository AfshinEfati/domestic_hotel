<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReservationManualReason extends Model
{
    protected $fillable = [
        'reservation_number',
        'provider_id',
        'reason',
    ];

    protected $casts = [
        'provider_id' => 'integer',
    ];

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class, 'reservation_number', 'reservation_number');
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }
}
