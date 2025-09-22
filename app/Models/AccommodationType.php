<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccommodationType extends Model
{
    //
    protected $fillable = [
        'id',
        'fa_name',
        'en_name',
        'created_at',
        'updated_at',
    ];
    protected $table = 'accommodation_types';
    protected $casts = [
        'id' => 'integer',
        'fa_name' => 'string',
        'en_name' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
    public function accommodations(): HasMany
    {
        return $this->hasMany(Accommodation::class, 'accommodation_type_id');
    }
}
