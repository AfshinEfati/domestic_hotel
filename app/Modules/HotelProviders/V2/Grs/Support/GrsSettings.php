<?php

namespace App\Modules\HotelProviders\V2\Grs\Support;

use App\Domain\Hotel\V2\GrsRefreshSettings as LegacyGrsRefreshSettings;
use App\Models\Provider;

final class GrsSettings
{
    public const PROVIDER_CODE = 'grs';

    /**
     * Transitional compatibility boundary.
     *
     * The legacy settings implementation remains authoritative until the GRS module
     * parity suite is green. Keeping this delegation prevents any configuration
     * behaviour change while the provider is moved behind the module boundary.
     *
     * @return array<string,mixed>
     */
    public static function from(Provider $provider): array
    {
        return LegacyGrsRefreshSettings::from($provider);
    }
}
