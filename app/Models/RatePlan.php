<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RatePlan extends Model
{
    protected $fillable = [
        'id',
        'accommodation_id',
        'fa_name',
        'en_name',
        'meal_type',
        'food_board_type',
        'cancelable',
        'sleeps',
        'min_stay',
        'max_stay',
        'facilities',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'id' => 'integer',
        'accommodation_id' => 'integer',
        'fa_name' => 'string',
        'en_name' => 'string',
        'meal_type' => 'string',
        'food_board_type' => 'string',
        'cancelable' => 'boolean',
        'sleeps' => 'integer',
        'min_stay' => 'integer',
        'max_stay' => 'integer',
        'facilities' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function accommodation(): BelongsTo
    {
        return $this->belongsTo(Accommodation::class);
    }
}
