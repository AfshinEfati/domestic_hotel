<?php

namespace App\Domain\Hotel\DTOs;

/** Simple typed DTOs for clarity (arrays used in adapters, typed here for ref) */
class ReserveRequestDTO
{
    public function __construct(
        public string $providerPropertyId,
        public string $checkIn,         // Y-m-d
        public string $checkOut,        // Y-m-d
        public string $roomTypeId,
        public ?string $ratePlanId,
        public int $roomsCount,
        public array $guests,           // [{first_name, last_name, national_id?, birthdate?, type: ADT/CHD/INF}]
        public array $contact,          // {mobile, email}
        public array $meta = [],        // provider-specific fields
    ) {}
}

class BookRequestDTO
{
    public function __construct(
        public string $reserveId,
        public array $payer,            // {full_name, national_id?, mobile, email}
        public array $payment,          // {method, amount, currency, ref_code?}
        public array $meta = [],
    ) {}
}

class ModifyRequestDTO
{
    public function __construct(
        public string $bookingIdOrReserveId,
        public array $changes,          // {dates?, guests_count?, rooms_count? ...}
        public array $meta = [],
    ) {}
}

class CancelRequestDTO
{
    public function __construct(
        public string $bookingIdOrReserveId,
        public ?string $reason = null,
        public array $meta = [],
    ) {}
}
