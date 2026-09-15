<?php

namespace App\Support\Reservation;

final class ReservationStatus
{
    public const int REQUESTED = 1;
    public const int RESERVED = 2;
    public const int RESERVATION_FAILED = 3;
    public const int PURCHASE_QUEUED = 4;
    public const int PURCHASE_IN_PROGRESS = 5;
    public const int PARTIALLY_ISSUED = 6;
    public const int ISSUE_FAILED = 7;
    public const int PAYMENT_REQUIRED = 8;
    public const int ISSUED = 9;
    public const int UNDER_REVIEW = 10;
    public const int REFUNDED = 11;

    public static function all(): array
    {
        return [
            self::REQUESTED,
            self::RESERVED,
            self::RESERVATION_FAILED,
            self::PURCHASE_QUEUED,
            self::PURCHASE_IN_PROGRESS,
            self::PARTIALLY_ISSUED,
            self::ISSUE_FAILED,
            self::PAYMENT_REQUIRED,
            self::ISSUED,
            self::UNDER_REVIEW,
            self::REFUNDED,
        ];
    }

    public static function isValid(int $status): bool
    {
        return in_array($status, self::all(), true);
    }

    public static function options(): array
    {
        return [
            self::REQUESTED => ['name' => 'requested', 'fa_name' => 'درخواست رزرو'],
            self::RESERVED => ['name' => 'reserved', 'fa_name' => 'رزرو شده'],
            self::RESERVATION_FAILED => ['name' => 'reservation_failed', 'fa_name' => 'رزرو ناموفق'],
            self::PURCHASE_QUEUED => ['name' => 'purchase_queued', 'fa_name' => 'در صف خرید'],
            self::PURCHASE_IN_PROGRESS => ['name' => 'purchase_in_progress', 'fa_name' => 'در حال تکمیل خرید'],
            self::PARTIALLY_ISSUED => ['name' => 'partially_issued', 'fa_name' => 'صدور ناقص'],
            self::ISSUE_FAILED => ['name' => 'issue_failed', 'fa_name' => 'صدور ناموفق'],
            self::PAYMENT_REQUIRED => ['name' => 'payment_required', 'fa_name' => 'نیازمند تکمیل پرداخت'],
            self::ISSUED => ['name' => 'issued', 'fa_name' => 'صدور موفق'],
            self::UNDER_REVIEW => ['name' => 'under_review', 'fa_name' => 'در دست بررسی'],
            self::REFUNDED => ['name' => 'refunded', 'fa_name' => 'استرداد شده'],
        ];
    }

    public static function get(int $status): ?array
    {
        $option = self::options()[$status] ?? null;

        if ($option === null) {
            return null;
        }

        return [
            ...$option,
            'code' => $status,
        ];
    }
}
