<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccommodationReview extends Model
{
    protected $fillable = [
        'accommodation_provider_map_id',
        'provider_id',
        'provider_review_id',
        'provider_user_id',
        'full_name',
        'comment',
        'comment_risk_level',
        'has_ever_booked',
        'ratings',
        'recommended',
        'provider_status',
        'registered_at',
        'provider_updated_at',
    ];

    protected $casts = [
        'accommodation_provider_map_id' => 'integer',
        'provider_id' => 'integer',
        'provider_user_id' => 'integer',
        'comment_risk_level' => 'decimal:4',
        'has_ever_booked' => 'boolean',
        'ratings' => 'array',
        'recommended' => 'boolean',
        'registered_at' => 'datetime',
        'provider_updated_at' => 'datetime',
    ];

    public function accommodationProviderMap(): BelongsTo
    {
        return $this->belongsTo(AccommodationProviderMap::class);
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }
}
