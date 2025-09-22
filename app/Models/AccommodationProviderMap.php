<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccommodationProviderMap extends Model
{
    protected $fillable = [
        'id',
        'accommodation_id',
        'provider_id',
        'provider_property_id',
        'fa_name',
        'en_name',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'id' => 'integer',
        'accommodation_id' => 'integer',
        'provider_id' => 'integer',
        'provider_property_id' => 'string',
        'fa_name' => 'string',
        'en_name' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function accommodation(): BelongsTo
    {
        return $this->belongsTo(Accommodation::class);
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }
}
