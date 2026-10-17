<?php

namespace App\Modules\HotelProviders\V2\Grs\Support;

use App\Models\Provider;

final class GrsSettings
{
    public const PROVIDER_CODE = 'grs';

    public static function defaults(): array
    {
        return [
            'default_days' => 90,
            'api_cooldown_minutes' => 15,
            'scheduler_enabled' => false,
        ];
    }

    public static function from(?Provider $provider): array
    {
        $defaults = self::defaults();
        $config = $provider?->config ?? [];
        $values = is_array($config) ? data_get($config, 'price_refresh', []) : [];
        $values = is_array($values) ? $values : [];

        return [
            'default_days' => self::boundedInt($values['default_days'] ?? null, $defaults['default_days'], 1, 3650),
            'api_cooldown_minutes' => self::boundedInt($values['api_cooldown_minutes'] ?? null, $defaults['api_cooldown_minutes'], 1, 1440),
            'scheduler_enabled' => self::boolValue($values['scheduler_enabled'] ?? null, $defaults['scheduler_enabled']),
        ];
    }

    private static function boundedInt(mixed $value, int $default, int $min, int $max): int
    {
        if (!is_int($value) && !(is_string($value) && ctype_digit($value))) {
            return $default;
        }

        $number = (int) $value;

        return $number >= $min && $number <= $max ? $number : $default;
    }

    private static function boolValue(mixed $value, bool $default): bool
    {
        return match ($value) {
            true, 1, '1' => true,
            false, 0, '0' => false,
            default => $default,
        };
    }
}
