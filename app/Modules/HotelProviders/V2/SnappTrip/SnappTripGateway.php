<?php

namespace App\Modules\HotelProviders\V2\SnappTrip;

use App\Models\Provider;
use App\Modules\HotelProviders\V2\SnappTrip\Infrastructure\Http\SnappTripClient;
use App\Modules\HotelProviders\V2\SnappTrip\Infrastructure\Mapper\SnappTripMapper;
use App\Modules\HotelProviders\V2\SnappTrip\Support\SnappTripMoney;
use Illuminate\Support\Collection;

final class SnappTripGateway
{
    public function __construct(
        private readonly SnappTripClient $client,
        private readonly SnappTripMapper $mapper,
    ) {
    }

    public function provider(): Provider
    {
        return $this->client->provider();
    }

    public function withRequestLogContext(
        ?int $reservationId = null,
        ?string $handlerClass = null,
        ?string $handlerMethod = null,
        int $attempt = 1,
        bool $force = false,
    ): self {
        return new self(
            $this->client->withRequestLogContext(
                $reservationId,
                $handlerClass,
                $handlerMethod,
                $attempt,
                $force,
            ),
            $this->mapper,
        );
    }

    public function cities(): Collection
    {
        return collect($this->client->cities())
            ->map(fn ($row) => is_array($row) ? $this->mapper->city($row) : null)
            ->filter()
            ->values();
    }

    public function cityHotels(int|string $providerCityId, int $limit = 100, int $offset = 0): Collection
    {
        return collect($this->client->cityHotels($providerCityId, $limit, $offset))
            ->filter(fn ($row) => is_array($row) && isset($row['id']))
            ->map(fn (array $row): array => [
                'provider_property_id' => (string) $row['id'],
                'provider_city_id' => isset($row['city_id']) ? (string) $row['city_id'] : (string) $providerCityId,
                'fa_name' => trim((string) ($row['name'] ?? '')),
                'provider_url' => isset($row['fa_url']) ? trim((string) $row['fa_url']) : null,
            ])
            ->values();
    }

    public function hotels(array $providerPropertyIds): Collection
    {
        return collect($this->client->hotels($providerPropertyIds))
            ->map(fn ($row) => is_array($row) ? $this->mapper->hotel($row) : null)
            ->filter()
            ->values();
    }

    /** @return Collection<string,array<int,array{name:string}>> */
    public function facilities(array $providerPropertyIds): Collection
    {
        return collect($this->client->facilities($providerPropertyIds))
            ->filter(fn ($row) => is_array($row) && isset($row['hotel_id']))
            ->mapWithKeys(function (array $row): array {
                $items = collect(is_array($row['facilities'] ?? null) ? $row['facilities'] : [])
                    ->map(fn ($facility) => is_array($facility) ? $this->mapper->facility($facility) : null)
                    ->filter()
                    ->values()
                    ->all();

                return [(string) $row['hotel_id'] => $items];
            });
    }

    /** @return Collection<string,array<int,array<string,mixed>>> */
    public function rooms(array $providerPropertyIds): Collection
    {
        return collect($this->client->rooms($providerPropertyIds))
            ->filter(fn ($row) => is_array($row) && isset($row['hotel_id']))
            ->mapWithKeys(function (array $row): array {
                $items = collect(is_array($row['rooms'] ?? null) ? $row['rooms'] : [])
                    ->map(fn ($room) => is_array($room) ? $this->mapper->room($room) : null)
                    ->filter()
                    ->values()
                    ->all();

                return [(string) $row['hotel_id'] => $items];
            });
    }

    /** @return array{rows: array<int,array<string,mixed>>,packages:array<int,array<string,mixed>>} */
    public function hotelCalendar(
        int|string $providerPropertyId,
        string $from,
        string $to,
        bool $foreigner = false,
    ): array {
        return $this->mapper->hotelCalendar(
            $this->client->hotelCalendar($providerPropertyId, $from, $to, $foreigner),
            $foreigner,
        );
    }

    /** @return array{rows: array<int,array<string,mixed>>,packages:array<int,array<string,mixed>>} */
    public function hotelAvailability(
        int|string $providerPropertyId,
        string $checkIn,
        string $checkOut,
        bool $foreigner = false,
    ): array {
        return $this->mapper->availability(
            $this->client->hotelAvailability($providerPropertyId, $checkIn, $checkOut, $foreigner),
            $foreigner,
        );
    }

    /** SnappTrip city search shape retained; request/output amounts cross the currency boundary here. */
    public function cityAvailability(array $payload): array
    {
        return SnappTripMoney::normalizeProviderPayload(
            $this->client->cityAvailability(
                SnappTripMoney::normalizeRequestPayload($payload),
            ),
        );
    }

    /** SnappTrip room-calendar shape retained, with all provider amounts normalized to IRR. */
    public function roomCalendar(
        int|string $providerPropertyId,
        int|string $providerRoomId,
        string $from,
        string $to,
        bool $foreigner = false,
    ): array {
        return SnappTripMoney::normalizeProviderPayload(
            $this->client->roomCalendar($providerPropertyId, $providerRoomId, $from, $to, $foreigner),
        );
    }

    public function balance(): ?int
    {
        return $this->mapper->balance($this->client->balance());
    }

    public function createBooking(array $payload): array
    {
        return $this->mapper->booking($this->client->createBooking($payload));
    }

    public function booking(string $reservationCode): array
    {
        return $this->mapper->booking($this->client->booking($reservationCode));
    }

    public function lockBooking(string $reservationCode): void
    {
        $this->client->lockBooking($reservationCode);
    }

    public function confirmBooking(string $reservationCode): array
    {
        return $this->mapper->booking($this->client->confirmBooking($reservationCode));
    }

    public function cancellationRules(
        int|string $providerPropertyId,
        int|string $providerRoomId,
        string $checkIn,
        string $checkOut,
    ): array {
        return $this->client->cancellationRules($providerPropertyId, $providerRoomId, $checkIn, $checkOut);
    }

    public function createCancellation(string $trackingCode): array
    {
        return $this->client->createCancellation($trackingCode);
    }

    public function cancellationInquiry(string $trackingCode): array
    {
        return $this->mapper->cancellationInquiry($this->client->cancellationInquiry($trackingCode));
    }

    public function acceptCancellation(string $trackingCode): void
    {
        $this->client->acceptCancellation($trackingCode);
    }

    public function rejectCancellation(string $trackingCode): void
    {
        $this->client->rejectCancellation($trackingCode);
    }

    public function health(): array
    {
        return $this->client->health();
    }
}
