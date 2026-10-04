<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RuntimeException;

class RatePlanProviderMap extends Model
{
    protected $fillable = [
        'id',
        'rate_plan_id',
        'provider_id',
        'accommodation_provider_map_id',
        'provider_rate_plan_id',
        'provider_metadata',
        'fa_name',
        'en_name',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'id' => 'integer',
        'rate_plan_id' => 'integer',
        'provider_id' => 'integer',
        'accommodation_provider_map_id' => 'integer',
        'provider_rate_plan_id' => 'string',
        'provider_metadata' => 'array',
        'fa_name' => 'string',
        'en_name' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $mapping): void {
            $accommodationMap = AccommodationProviderMap::query()
                ->find($mapping->accommodation_provider_map_id);
            $local = RatePlan::query()->find($mapping->rate_plan_id);

            if (
                $accommodationMap === null
                || $local === null
                || (int) $accommodationMap->provider_id !== (int) $mapping->provider_id
                || (int) $accommodationMap->accommodation_id !== (int) $local->accommodation_id
            ) {
                throw new RuntimeException(
                    'Rate plan provider mapping must belong to the same provider and accommodation map.'
                );
            }
        });
    }

    public function ratePlan(): BelongsTo
    {
        return $this->belongsTo(RatePlan::class);
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function accommodationProviderMap(): BelongsTo
    {
        return $this->belongsTo(AccommodationProviderMap::class);
    }
}
