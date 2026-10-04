<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RuntimeException;

class RoomTypeProviderMap extends Model
{
    protected $fillable = [
        'id',
        'room_type_id',
        'provider_id',
        'accommodation_provider_map_id',
        'provider_room_type_id',
        'provider_adult_capacity',
        'provider_child_capacity',
        'provider_extra_capacity',
        'fa_name',
        'en_name',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'id' => 'integer',
        'room_type_id' => 'integer',
        'provider_id' => 'integer',
        'accommodation_provider_map_id' => 'integer',
        'provider_room_type_id' => 'string',
        'provider_adult_capacity' => 'integer',
        'provider_child_capacity' => 'integer',
        'provider_extra_capacity' => 'integer',
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
            $local = RoomType::query()->find($mapping->room_type_id);

            if (
                $accommodationMap === null
                || $local === null
                || (int) $accommodationMap->provider_id !== (int) $mapping->provider_id
                || (int) $accommodationMap->accommodation_id !== (int) $local->accommodation_id
            ) {
                throw new RuntimeException(
                    'Room type provider mapping must belong to the same provider and accommodation map.'
                );
            }
        });
    }

    public function roomType(): BelongsTo
    {
        return $this->belongsTo(RoomType::class);
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
