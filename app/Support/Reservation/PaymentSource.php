<?php

namespace App\Support\Reservation;

final class PaymentSource
{
    public const int CREDIT = 1;
    public const int GATEWAY = 2;
    public const int CARD_TO_CARD = 3;
    public const int CASH = 4;

    public static function all(): array
    {
        return [
            self::CREDIT,
            self::GATEWAY,
            self::CARD_TO_CARD,
            self::CASH,
        ];
    }

    public static function isValid(int $source): bool
    {
        return in_array($source, self::all(), true);
    }

    public static function options(): array
    {
        return [
            self::CREDIT => ['name' => 'credit', 'fa_name' => 'اعتباری (از اعتبار تامین‌کننده)', 'code' => self::CREDIT],
            self::GATEWAY => ['name' => 'gateway', 'fa_name' => 'درگاه/انتقال بانکی', 'code' => self::GATEWAY],
            self::CARD_TO_CARD => ['name' => 'card_to_card', 'fa_name' => 'کارت به کارت', 'code' => self::CARD_TO_CARD],
            self::CASH => ['name' => 'cash', 'fa_name' => 'نقدی', 'code' => self::CASH],
        ];
    }

    public static function get(int $source): ?array
    {
        return self::options()[$source] ?? null;
    }
}
