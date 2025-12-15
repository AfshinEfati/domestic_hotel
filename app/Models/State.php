<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class State extends Model
{
    protected $fillable = [
        'id',
        'country_id',
        'fa_name',
        'en_name',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'id' => 'integer',
        'country_id' => 'integer',
        'fa_name' => 'string',
        'en_name' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }
    public function cities(): HasMany
    {
        return $this->hasMany(City::class);
    }
}
