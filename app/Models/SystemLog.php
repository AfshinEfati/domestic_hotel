<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Model;

class SystemLog extends Model
{
    protected $fillable = [
        'level',
        'method',
        'message',
        'provider_id',
        'context',
    ];

    protected $casts = [
        'provider_id' => 'integer',
        'context' => AsArrayObject::class,
    ];
}
