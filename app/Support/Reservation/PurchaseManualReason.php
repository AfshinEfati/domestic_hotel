<?php

namespace App\Support\Reservation;

final class PurchaseManualReason
{
    public const int MATCHED_RULE = 1;
    public const int OFFLINE_PROVIDER = 2;
    public const int INACTIVE_PROVIDER = 3;
    public const int INSUFFICIENT_CREDIT = 4;
    public const int CREDIT_UNAVAILABLE = 5;

    public static function get(int $reason): ?array
    {
        return match ($reason) {
            self::MATCHED_RULE => ['code' => self::MATCHED_RULE, 'name' => 'matched_rule', 'fa_name' => 'قانون خرید دستی'],
            self::OFFLINE_PROVIDER => ['code' => self::OFFLINE_PROVIDER, 'name' => 'offline_provider', 'fa_name' => 'تأمین‌کننده آفلاین'],
            self::INACTIVE_PROVIDER => ['code' => self::INACTIVE_PROVIDER, 'name' => 'inactive_provider', 'fa_name' => 'تأمین‌کننده غیرفعال'],
            self::INSUFFICIENT_CREDIT => ['code' => self::INSUFFICIENT_CREDIT, 'name' => 'insufficient_credit', 'fa_name' => 'اعتبار ناکافی تأمین‌کننده'],
            self::CREDIT_UNAVAILABLE => ['code' => self::CREDIT_UNAVAILABLE, 'name' => 'credit_unavailable', 'fa_name' => 'اطلاعات اعتبار تأمین‌کننده ناموجود'],
            default => null,
        };
    }
}
