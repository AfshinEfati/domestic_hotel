<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Accommodation extends Model
{
    protected $fillable = [
        'id',
        'city_id',
        'fa_name',
        'en_name',
        'accommodation_type_id',
        'star',
        'grade',
        'address',
        'lat',
        'lng',
        'is_active',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'id' => 'integer',
        'city_id' => 'integer',
        'fa_name' => 'string',
        'en_name' => 'string',
        'accommodation_type_id' => 'integer',
        'star' => 'integer',
        'grade' => 'string',
        'address' => 'string',
        'lat' => 'float',
        'lng' => 'float',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(AccommodationType::class, 'accommodation_type_id');
    }
    public function facilities(): BelongsToMany
    {
        return $this->belongsToMany(Facility::class, 'accommodation_facility')
            ->withTimestamps();
    }

    public function rules(): BelongsToMany
    {
        return $this->belongsToMany(Rule::class, 'accommodation_rule')
            ->withPivot('value')
            ->withTimestamps();
    }
}
