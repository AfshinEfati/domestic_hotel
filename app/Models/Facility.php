<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Facility extends Model
{
    protected $fillable = [
        'facility_group_id',
        'fa_name',
        'en_name',
    ];

    protected $casts = [
        'facility_group_id' => 'integer',
        'fa_name'           => 'string',
        'en_name'           => 'string',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(FacilityGroup::class, 'facility_group_id');
    }

    public function accommodations(): BelongsToMany
    {
        return $this->belongsToMany(Accommodation::class, 'accommodation_facility')
            ->withTimestamps();
    }

}

