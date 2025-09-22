<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoomTypeProviderMap extends Model
{
    protected $fillable = [
        'id',
        'room_type_id',
        'provider_id',
        'provider_room_type_id',
        'fa_name',
        'en_name',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'id' => 'integer',
        'room_type_id' => 'integer',
        'provider_id' => 'integer',
        'provider_room_type_id' => 'string',
        'fa_name' => 'string',
        'en_name' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function roomType(): BelongsTo
    {
        return $this->belongsTo(RoomType::class);
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }
}
