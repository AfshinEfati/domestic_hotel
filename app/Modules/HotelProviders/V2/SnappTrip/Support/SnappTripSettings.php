<?php

namespace App\Modules\HotelProviders\V2\SnappTrip\Support;

use App\Models\Provider;

final class SnappTripSettings
{
    public const PROVIDER_CODE = 'snap';
    public const API_KEY_PLACEHOLDER = 'api_key_snapptrip';

    public static function defaults(): array
    {
        return [
            'base_url' => 'https://b2bapiv2.snapptrip.com/',
            'api_key' => self::API_KEY_PLACEHOLDER,
            'rate_limit' => [
                'max_requests' => 120,
                'window_minutes' => 1,
            ],
            'static_sync' => [
                'scheduler_enabled' => true,
                'interval_hours' => 24,
            ],
            'price_refresh' => [
                'scheduler_enabled' => true,
                'default_days' => 90,
            ],
            'purchase' => [
                'online_enabled' => false,
            ],
        ];
    }

    public static function from(?Provider $provider): array
    {
        $defaults = self::defaults();
        $config = is_array($provider?->config) ? $provider->config : [];
        $merged = array_replace_recursive($defaults, $config);
        $rawBaseUrl = trim((string) ($merged['base_url'] ?? ''));

        return [
            'base_url' => $rawBaseUrl === '' ? '' : rtrim($rawBaseUrl, '/').'/',
            'api_key' => trim((string) ($merged['api_key'] ?? '')),
            'rate_limit' => [
                'max_requests' => self::boundedInt(
                    data_get($merged, 'rate_limit.max_requests'),
                    120,
                    1,
                    10000,
                ),
                'window_minutes' => self::boundedInt(
                    data_get($merged, 'rate_limit.window_minutes'),
                    1,
                    1,
                    1440,
                ),
            ],
            'static_sync' => [
                'scheduler_enabled' => self::boolValue(
                    data_get($merged, 'static_sync.scheduler_enabled'),
                    true,
                ),
                'interval_hours' => self::boundedInt(
                    data_get($merged, 'static_sync.interval_hours'),
                    24,
                    1,
                    720,
                ),
            ],
            'price_refresh' => [
                'scheduler_enabled' => self::boolValue(
                    data_get($merged, 'price_refresh.scheduler_enabled'),
                    true,
                ),
                'default_days' => self::boundedInt(
                    data_get($merged, 'price_refresh.default_days'),
                    90,
                    1,
                    3650,
                ),
            ],
            'purchase' => [
                'online_enabled' => self::boolValue(
                    data_get($merged, 'purchase.online_enabled'),
                    false,
                ),
            ],
        ];
    }

    private static function boundedInt(mixed $value, int $default, int $min, int $max): int
    {
        if (!is_numeric($value)) {
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
