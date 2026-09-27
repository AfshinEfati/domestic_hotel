<?php

namespace App\Support\Reservation;

final class PaymentSource
{
    /**
     * Legacy value kept only for existing rows. New manual-purchase payments
     * must use one of CARD, GATEWAY, BANK or CASH.
     */
    public const CREDIT = 1;
    public const GATEWAY = 2;
    public const CARD = 3;
    public const CARD_TO_CARD = self::CARD;
    public const CASH = 4;
    public const BANK = 5;

    public static function all(): array
    {
        return [
            self::CREDIT,
            self::GATEWAY,
            self::CARD,
            self::CASH,
            self::BANK,
        ];
    }

    public static function manualPurchaseSources(): array
    {
        return [
            self::CARD,
            self::GATEWAY,
            self::BANK,
            self::CASH,
        ];
    }

    public static function isValid(int $source): bool
    {
        return in_array($source, self::all(), true);
    }

    public static function isValidManualPurchaseSource(int $source): bool
    {
        return in_array($source, self::manualPurchaseSources(), true);
    }

    public static function options(): array
    {
        return [
            self::CREDIT => ['name' => 'credit', 'fa_name' => 'اعتباری - قدیمی', 'code' => self::CREDIT],
            self::GATEWAY => ['name' => 'gateway', 'fa_name' => 'درگاه', 'code' => self::GATEWAY],
            self::CARD => ['name' => 'card', 'fa_name' => 'کارت', 'code' => self::CARD],
            self::CASH => ['name' => 'cash', 'fa_name' => 'نقدی', 'code' => self::CASH],
            self::BANK => ['name' => 'bank', 'fa_name' => 'بانک', 'code' => self::BANK],
        ];
    }

    public static function get(int $source): ?array
    {
        return self::options()[$source] ?? null;
    }
}
