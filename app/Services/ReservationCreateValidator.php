<?php

namespace App\Services;

use App\Domain\Hotel\Contracts\ProviderAdapterInterface;
use App\Models\HotelChildPolicy;
use App\Models\RoomCalendar;
use App\Repositories\Contracts\ProviderRepositoryInterface;
use App\Repositories\Contracts\RoomCalendarRepositoryInterface;
use App\Support\Reservation\ReservationGuestType;
use App\Support\Reservation\ReservationStatus;
use Carbon\CarbonImmutable;
use Error;
use Illuminate\Support\Collection;
use Throwable;

/** Checks the exact calendars offered to the customer, night by night; never searches for another provider. */
readonly class ReservationCreateValidator
{
    public function __construct(
        private RoomCalendarRepositoryInterface $calendars,
        private ProviderRepositoryInterface     $providers,
    ) {}

    // ...
}
