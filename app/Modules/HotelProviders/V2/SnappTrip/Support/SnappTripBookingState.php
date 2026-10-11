<?php

namespace App\Modules\HotelProviders\V2\SnappTrip\Support;

final class SnappTripBookingState
{
    public const CONFIRMED = 'confirmed';
    public const DRAFT = 'draft';
    public const PAYMENT_ON = 'payment_on';
    public const FAILED = 'failed';
    public const PAYMENT_FAILED = 'payment_failed';
    public const EXPIRED = 'expired';
    public const AVAILABILITY_FAILED = 'availability_failed';
    public const AVAILABILITY_CHECKING = 'availability_checking';
    public const AVAILABILITY_PENDING = 'availability_pending';
    public const AVAILABILITY_CONFIRMED = 'availability_confirmed';
    public const AVAILABILITY_REJECTED = 'availability_rejected';
    public const OVERBOOK = 'overbook';

    public static function normalize(mixed $state): ?string
    {
        if (!is_string($state) && !is_numeric($state)) {
            return null;
        }

        $state = strtolower(trim((string) $state));

        return $state === '' ? null : $state;
    }

    public static function isConfirmed(?string $state): bool
    {
        return $state === self::CONFIRMED;
    }

    public static function needsConfirm(?string $state): bool
    {
        return in_array($state, [self::DRAFT, self::PAYMENT_ON], true);
    }

    public static function isPending(?string $state): bool
    {
        return in_array($state, [
            // A mandatory status GET immediately after confirm can still be briefly
            // behind the confirm request. Treat these as pollable after confirm; the
            // handler's needsConfirm branch still confirms them before this check when
            // it is looking at a pre-confirm status response.
            self::DRAFT,
            self::PAYMENT_ON,
            self::AVAILABILITY_CHECKING,
            self::AVAILABILITY_PENDING,
            self::AVAILABILITY_CONFIRMED,
        ], true);
    }

    public static function isTerminalFailure(?string $state): bool
    {
        return in_array($state, [
            self::FAILED,
            self::PAYMENT_FAILED,
            self::EXPIRED,
            self::AVAILABILITY_FAILED,
            self::AVAILABILITY_REJECTED,
            self::OVERBOOK,
        ], true);
    }
}
