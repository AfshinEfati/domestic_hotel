<?php

namespace App\Domain\Hotel\Contracts;

use Illuminate\Support\Collection;
use DateTimeInterface;

interface ProviderAdapterInterface
{
    public function code(): string;

    public function withRequestLogContext(
        ?int $reservationId = null,
        ?string $handlerClass = null,
        ?string $handlerMethod = null,
        int $attempt = 1,
        bool $force = false,
    ): static;

    /** Cities */
    public function fetchCities(): Collection; // [{id, fa_name, en_name?, country_id?, province_id?}]

    /** Properties (Hotels) */
    public function fetchPropertiesByCity(string $providerCityId, int $page = 1, int $count = 100): Collection;
    // [{property_id, fa_name, en_name?, city_provider_id, lat?, lng?, address?, star?, type?}]

    /** Room Types & Rate Plans (if available per provider) */
    public function fetchRoomTypes(string $providerPropertyId): ?Collection; // [{room_type_id, fa_name, en_name?, capacity?, extra?}]
    public function fetchRatePlans(string $providerPropertyId): Collection; // [{rate_plan_id, fa_name, en_name?, meal_type?, sleeps?, cancelable?}]

    /** Availability Calendar */
    public function fetchAvailability(
        string $providerPropertyId,
        DateTimeInterface $from,
        DateTimeInterface $to
    ): Collection;
    // [{day, inventory?, rack_rate?, daily_rate?, grs_rate?, min_stay?, max_stay?, cta?, ctd?, closed?, room_type_id, rate_plan_id?, rate_plan_name?}]

    /** Reservation Flow */
    public function reserve(array $payload): array;          // returns normalized: {reserve_id, expires_at, price_summary, hold_details}
    public function extendExpire(string $reserveId): array;  // returns normalized: {reserve_id, new_expires_at}
    public function book(array $payload): array;             // returns normalized: {pnr?, reference_code, status, voucher_url?}
    public function modify(array $payload): array;           // returns normalized: {reserve_id|booking_id, status, difference_price?}
    public function cancel(array $payload): array;           // returns normalized: {reserve_id|booking_id, status, refund_amount?}

    /** Reserve/Booking list & details */
    public function reservesList(array $filters = []): Collection; // [{reserve_id, status, created_at, property_id, guest_name, total_price}]
    public function reserveDetails(string $reserveId): array;      // full normalized details

    /** Webhooks: Return array of types supported to help routing */
    public function supportedWebhooks(): array; // e.g. ['AvailableChanged','ReserveChanged','PropertyChanged']

    public function fetchFacilities();

    public function fetchProperties();
}
