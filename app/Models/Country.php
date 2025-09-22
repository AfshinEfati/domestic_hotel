<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    protected $fillable = [
        'id',
        'fa_name',
        'en_name',
        'iso2',
        'iso3',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'id' => 'integer',
        'fa_name' => 'string',
        'en_name' => 'string',
        'iso2' => 'string',
        'iso3' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
