<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProviderStayPackage extends Model
{
    protected $fillable = [
        'accommodation_provider_map_id',
        'provider_id',
        'room_type_provider_map_id',
        'provider_room_type_id',
        'title',
        'check_in',
        'check_out',
        'is_active',
        'last_seen_at',
        'package_key',
    ];

    protected $casts = [
        'accommodation_provider_map_id' => 'integer',
        'provider_id' => 'integer',
        'room_type_provider_map_id' => 'integer',
        'check_in' => 'date',
        'check_out' => 'date',
        'is_active' => 'boolean',
        'last_seen_at' => 'datetime',
    ];

    public function accommodationProviderMap(): BelongsTo
    {
        return $this->belongsTo(AccommodationProviderMap::class);
    }

    public function roomTypeProviderMap(): BelongsTo
    {
        return $this->belongsTo(RoomTypeProviderMap::class);
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }
}
