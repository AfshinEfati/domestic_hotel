<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoomType extends Model
{
    protected $fillable = [
        'id',
        'accommodation_id',
        'room_type_name_id',
        'fa_name',
        'en_name',
        'capacity',
        'extra_capacity',
        'single_bed_count',
        'double_bed_count',
        'sofa_bed_count',
        'out_of_service',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'id' => 'integer',
        'accommodation_id' => 'integer',
        'fa_name' => 'string',
        'en_name' => 'string',
        'capacity' => 'integer',
        'extra_capacity' => 'integer',
        'single_bed_count' => 'integer',
        'double_bed_count' => 'integer',
        'sofa_bed_count' => 'integer',
        'out_of_service' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function accommodation(): BelongsTo
    {
        return $this->belongsTo(Accommodation::class);
    }

    public function roomTypeName(): BelongsTo
    {
        return $this->belongsTo(RoomTypeName::class);
    }
}
