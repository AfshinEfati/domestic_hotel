<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class City extends Model
{
    protected $fillable = [
        'id',
        'country_id',
        'state_id',
        'fa_name',
        'en_name',
        'lat',
        'lng',
        'osm_id',
        'is_popular' ,
        'is_active' ,
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'id' => 'integer',
        'country_id' => 'integer',
        'state_id' => 'integer',
        'fa_name' => 'string',
        'en_name' => 'string',
        'lat' => 'float',
        'lng' => 'float',
        'osm_id' => 'string',
        'is_popular' => 'boolean',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }
}
