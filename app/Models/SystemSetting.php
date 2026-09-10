<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Stores runtime business configuration for the Domestic Hotel GDS.
 *
 * These settings are persisted in the database and managed through Admin APIs so
 * operational/business behavior can be changed after deployment without editing
 * source code, config files, or environment variables.
 *
 * This model represents system-wide configuration and is not related to an
 * individual accommodation/hotel entity.
 */
class SystemSetting extends Model
{
    protected $table = 'system_settings';

    protected $fillable = [
        'key',
        'group',
        'value',
        'value_type',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
