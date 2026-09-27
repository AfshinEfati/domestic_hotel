<?php

namespace App\Support\Reservation;

final class ReservationStatus
{
    public const REQUESTED = 1;
    public const CHECKED = 2;
    public const NO_AVAILABILITY = 3;
    public const READY_FOR_PAYMENT = 4;
    public const BOOK_REQUESTED = 5;
    public const ISSUE_SUCCESS = 6;

    // وضعیت ۷ فقط از پنل انجام میشه . این وضعیت یعنی خرید کلا ریجکت شده .
    // نه به صورت آنلاین و نه آفلاین این وضعیت قابل تغییر نیست و خرید کنسل باید بشه
    public const ISSUE_FAILED = 7;
    public const PAYMENT_REQUIRED = 8;
    public const COMPLETED = 9;
    public const UNDER_REVIEW = 10;
    public const REFUNDED = 11;
    public const PURCHASE_REFUND = 12;

    // Compatibility names for existing integrations. Numeric codes are authoritative.
    public const RESERVED = self::CHECKED;
    public const RESERVATION_FAILED = self::NO_AVAILABILITY;
    public const PURCHASE_QUEUED = self::READY_FOR_PAYMENT;
    public const PURCHASE_IN_PROGRESS = self::BOOK_REQUESTED;
    public const PARTIALLY_ISSUED = self::ISSUE_SUCCESS;
    public const ISSUED = self::COMPLETED;

    public static function all(): array
    {
        return [
            self::REQUESTED, self::CHECKED, self::NO_AVAILABILITY,
            self::READY_FOR_PAYMENT, self::BOOK_REQUESTED, self::ISSUE_SUCCESS,
            self::ISSUE_FAILED, self::PAYMENT_REQUIRED, self::COMPLETED,
            self::UNDER_REVIEW, self::REFUNDED, self::PURCHASE_REFUND,
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
            self::CHECKED => ['name' => 'checked', 'fa_name' => 'بررسی‌شده'],
            self::NO_AVAILABILITY => ['name' => 'no_availability', 'fa_name' => 'فاقد ظرفیت و بسته‌شده'],
            self::READY_FOR_PAYMENT => ['name' => 'ready_for_payment', 'fa_name' => 'آماده پرداخت'],
            self::BOOK_REQUESTED => ['name' => 'book_requested', 'fa_name' => 'پرداخت و درخواست خرید'],
            self::ISSUE_SUCCESS => ['name' => 'issue_success', 'fa_name' => 'صدور موفق'],
            self::ISSUE_FAILED => ['name' => 'issue_failed', 'fa_name' => 'صدور ناموفق'],
            self::PAYMENT_REQUIRED => ['name' => 'payment_required', 'fa_name' => 'صدور موفق، نیازمند تکمیل مالی'],
            self::COMPLETED => ['name' => 'completed', 'fa_name' => 'تکمیل‌شده'],
            self::UNDER_REVIEW => ['name' => 'under_review', 'fa_name' => 'در دست بررسی اپراتور'],
            self::REFUNDED => ['name' => 'refunded', 'fa_name' => 'استرداد کامل'],
            self::PURCHASE_REFUND => ['name' => 'purchase_refund', 'fa_name' => 'استرداد خرید'],
        ];
    }

    public static function get(int $status): ?array
    {
        $option = self::options()[$status] ?? null;
        return $option === null ? null : [...$option, 'code' => $status];
    }
}
