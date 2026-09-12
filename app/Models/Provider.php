<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Represents a hotel inventory and reservation provider.
 */
class Provider extends Model
{
    protected $fillable = [
        'id',
        'fa_name',
        'en_name',
        'code',
        'config',
        'is_active',
        'is_online',
        'created_at',
        'updated_at',
        'auth_token',
        'expire_at',
    ];

    protected $casts = [
        'id' => 'integer',
        'fa_name' => 'string',
        'en_name' => 'string',
        'code' => 'string',
        'config' => 'array',
        'is_active' => 'boolean',
        'is_online' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the city mappings for the provider.
     *
     * The relationship uses `provider_id` as the foreign key.
     *
     * @return HasMany<ProviderCityMap>
     */
    public function cityMaps(): HasMany
    {
        return $this->hasMany(ProviderCityMap::class);
    }

    public function reservationPurchases(): HasMany
    {
        return $this->hasMany(ReservationPurchase::class);
    }

    public function quotedReservationPurchases(): HasMany
    {
        return $this->hasMany(ReservationPurchase::class, 'quoted_provider_id');
    }
}
