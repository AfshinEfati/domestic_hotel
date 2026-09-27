<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

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
        'provider_type',
        'accommodation_id',
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
        'provider_type' => 'integer',
        'accommodation_id' => 'integer',
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
    public function accommodation(): BelongsTo
    {
        return $this->belongsTo(Accommodation::class);
    }

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

    public function creditBalance(): HasOne
    {
        return $this->hasOne(ProviderCreditBalance::class);
    }

    public function providerRequests(): HasMany
    {
        return $this->hasMany(ProviderRequest::class);
    }
}
