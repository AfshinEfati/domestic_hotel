<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProviderRequest extends Model
{
    use MassPrunable;

    protected $fillable = [
        'provider_id',
        'reservation_id',
        'handler_class',
        'handler_method',
        'http_method',
        'url',
        'request_body',
        'response_body',
        'http_status',
        'status',
        'attempt',
        'exception_class',
        'error_message',
        'started_at',
        'finished_at',
        'duration_ms',
        'expires_at',
    ];

    protected $casts = [
        'provider_id' => 'integer',
        'reservation_id' => 'integer',
        'request_body' => 'array',
        'response_body' => 'array',
        'http_status' => 'integer',
        'status' => 'integer',
        'attempt' => 'integer',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'duration_ms' => 'integer',
        'expires_at' => 'datetime',
    ];

    public function prunable(): Builder
    {
        return static::query()->where('expires_at', '<=', now());
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }
}
