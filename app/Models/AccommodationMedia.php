<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccommodationMedia extends Model
{
    protected $fillable = [
        'accommodation_provider_map_id',
        'provider_id',
        'type',
        'provider_media_key',
        'url',
        'title',
        'description',
        'sort_order',
    ];

    protected $casts = [
        'accommodation_provider_map_id' => 'integer',
        'provider_id' => 'integer',
        'sort_order' => 'integer',
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
