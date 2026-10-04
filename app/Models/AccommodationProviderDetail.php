<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccommodationProviderDetail extends Model
{
    protected $fillable = [
        'accommodation_provider_map_id',
        'description',
        'provider_url',
        'is_marketplace',
        'check_in_time',
        'check_out_time',
        'cancellation_policy',
        'foreigners_fee',
        'free_transfer_policy',
        'free_transfers',
        'ratings',
    ];

    protected $casts = [
        'accommodation_provider_map_id' => 'integer',
        'is_marketplace' => 'boolean',
        'foreigners_fee' => 'boolean',
        'free_transfers' => 'array',
        'ratings' => 'array',
    ];

    public function accommodationProviderMap(): BelongsTo
    {
        return $this->belongsTo(AccommodationProviderMap::class);
    }
}
