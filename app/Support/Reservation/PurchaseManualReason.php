<?php

namespace App\Support\Reservation;

final class PurchaseManualReason
{
    public const int MATCHED_RULE = 1;
    public const int OFFLINE_PROVIDER = 2;

    public static function get(int $reason): ?array
    {
        return match ($reason) {
            self::MATCHED_RULE => ['code' => self::MATCHED_RULE, 'name' => 'matched_rule', 'fa_name' => 'قانون خرید دستی'],
            self::OFFLINE_PROVIDER => ['code' => self::OFFLINE_PROVIDER, 'name' => 'offline_provider', 'fa_name' => 'تأمین‌کننده آفلاین'],
            default => null,
        };
    }
}
