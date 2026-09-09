<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Rule extends Model
{
    protected $fillable = [
        'hotel_id',
        'rule_category_id',
        'provider_rule_id',
        'rule_id',
        'type',
        'name',
        'name_ar',
        'name_en',
        'conditions',
        'room_type_id',
        'rate_plan_id',
        'description',
        'description_ar',
        'description_en',
        'status',
    ];

    protected $casts = [
        'conditions' => 'array',
        'status' => 'boolean',
        'rule_id' => 'integer',
    ];

    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Accommodation::class, 'hotel_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(RuleCategory::class, 'rule_category_id');
    }
}
