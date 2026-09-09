<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RoomTypeName extends Model
{

    protected $fillable = ['fa_name', 'en_name'];

    public function roomTypes(): RoomTypeName|HasMany
    {
        return $this->hasMany(RoomType::class);
    }
}
