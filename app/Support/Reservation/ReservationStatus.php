<?php

namespace App\Support\Reservation;

final class ReservationStatus
{
    public const REQUESTED = 1;
    public const RESERVED = 2;
    public const RESERVATION_FAILED = 3;
    public const PURCHASE_QUEUED = 4;
    public const PURCHASE_IN_PROGRESS = 5;
    public const PARTIALLY_ISSUED = 6;
    public const ISSUE_FAILED = 7;
    public const PAYMENT_REQUIRED = 8;
    public const ISSUED = 9;
    public const UNDER_REVIEW = 10;
    public const REFUNDED = 11;
    public const PURCHASE_REFUND = 12;

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
            self::PURCHASE_REFUND,
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
            self::PURCHASE_REFUND => ['name' => 'purchase_refund', 'fa_name' => 'استرداد خرید'],
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
