<?php

namespace App\Support\Reservation;

final class ManualPurchaseReason
{
    public const RULE_MATCHED = 1;
    public const PROVIDER_OFFLINE_ONLY = 2;
    public const PROVIDER_FAILURE = 3;
    public const OPERATOR_OVERRIDE = 4;

    public static function all(): array
    {
        return [
            self::RULE_MATCHED,
            self::PROVIDER_OFFLINE_ONLY,
            self::PROVIDER_FAILURE,
            self::OPERATOR_OVERRIDE,
        ];
    }

    public static function isValid(int $reason): bool
    {
        return in_array($reason, self::all(), true);
    }

    public static function options(): array
    {
        return [
            self::RULE_MATCHED => ['name' => 'rule_matched', 'fa_name' => 'مطابق قانون خرید دستی فعال', 'code' => self::RULE_MATCHED],
            self::PROVIDER_OFFLINE_ONLY => ['name' => 'provider_offline_only', 'fa_name' => 'تامین‌کننده فاقد قابلیت آنلاین (هتل مستقیم)', 'code' => self::PROVIDER_OFFLINE_ONLY],
            self::PROVIDER_FAILURE => ['name' => 'provider_failure', 'fa_name' => 'عدم پاسخ یا شکست خرید آنلاین تامین‌کننده', 'code' => self::PROVIDER_FAILURE],
            self::OPERATOR_OVERRIDE => ['name' => 'operator_override', 'fa_name' => 'تصمیم دستی کارشناس بدون قانون/خطا', 'code' => self::OPERATOR_OVERRIDE],
        ];
    }

    public static function get(int $reason): ?array
    {
        return self::options()[$reason] ?? null;
    }
}
