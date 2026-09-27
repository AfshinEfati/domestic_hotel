<?php

namespace App\Support\Provider;

final class ProviderType
{
    public const INTEGRATION = 1;
    public const HOTEL_DIRECT = 2;

    public static function all(): array
    {
        return [
            self::INTEGRATION,
            self::HOTEL_DIRECT,
        ];
    }

    public static function isValid(int $type): bool
    {
        return in_array($type, self::all(), true);
    }

    public static function options(): array
    {
        return [
            self::INTEGRATION => [
                'name' => 'integration',
                'fa_name' => 'تأمین‌کننده',
                'code' => self::INTEGRATION,
            ],
            self::HOTEL_DIRECT => [
                'name' => 'hotel_direct',
                'fa_name' => 'خرید مستقیم از هتل',
                'code' => self::HOTEL_DIRECT,
            ],
        ];
    }

    public static function get(int $type): ?array
    {
        return self::options()[$type] ?? null;
    }
}
