<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HotelSetting extends Model
{
    protected $fillable = [
        'key',
        'group',
        'value',
        'value_type',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
