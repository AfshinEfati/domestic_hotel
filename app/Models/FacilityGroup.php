<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FacilityGroup extends Model
{
    protected $fillable = [
        'fa_name',
        'en_name',
    ];

    protected $casts = [
        'fa_name' => 'string',
        'en_name' => 'string',
    ];

    public function facilities(): HasMany
    {
        return $this->hasMany(Facility::class);
    }
}
