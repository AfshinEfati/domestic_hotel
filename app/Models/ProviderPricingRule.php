<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProviderPricingRule extends Model
{
    protected $fillable = [
        'provider_id',
        'percentage',
        'fixed_amount',
        'is_active',
    ];

    protected $casts = [
        'provider_id' => 'integer',
        'percentage' => 'float',
        'fixed_amount' => 'integer',
        'is_active' => 'boolean',
    ];

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }
}
