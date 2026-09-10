<?php

namespace App\Support\Hotel;

final class HotelSettingValueType
{
    public const STRING = 'string';
    public const INTEGER = 'integer';
    public const FLOAT = 'float';
    public const BOOLEAN = 'boolean';
    public const JSON = 'json';

    public static function values(): array
    {
        return [
            self::STRING,
            self::INTEGER,
            self::FLOAT,
            self::BOOLEAN,
            self::JSON,
        ];
    }

    public static function isValid(string $value): bool
    {
        return in_array($value, self::values(), true);
    }

    private function __construct()
    {
    }
}
