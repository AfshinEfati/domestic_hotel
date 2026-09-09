<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HotelChildPolicy extends Model
{
    protected $table = 'hotel_child_policy';

    protected $fillable = [
        'accommodation_id',
        'max_infant_age',
        'max_child_age',
        'infant_when_disabled',
        'child_when_disabled',
        'infant_service_condition',
        'child_service_condition',
        'max_children_covered',
        'max_infants_covered',
        'infant_pricing_type',
        'infant_pricing_value',
        'child_pricing_type',
        'child_pricing_value',
        'description',
        'status',
    ];

    protected $casts = [
        'accommodation_id' => 'integer',
        'max_infant_age' => 'integer',
        'max_child_age' => 'integer',
        'max_children_covered' => 'integer',
        'max_infants_covered' => 'integer',
        'infant_pricing_value' => 'integer',
        'child_pricing_value' => 'integer',
        'status' => 'boolean',
    ];

    public function accommodation(): BelongsTo
    {
        return $this->belongsTo(Accommodation::class, 'accommodation_id');
    }
}
