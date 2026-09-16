<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReservationManualReason extends Model
{
    protected $fillable = [
        'reservation_id',
        'reason',
    ];

    protected $casts = [
        'reservation_id' => 'integer',
    ];

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }
}
