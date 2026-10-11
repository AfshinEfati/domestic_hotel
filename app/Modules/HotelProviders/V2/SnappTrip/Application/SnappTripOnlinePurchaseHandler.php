<?php

namespace App\Modules\HotelProviders\V2\SnappTrip\Application;

use App\Models\ReservationGuest;
use App\Models\ReservationPurchase;
use App\Models\ReservationRoom;
use App\Modules\HotelProviders\V2\Shared\Contracts\OnlinePurchaseHandler;
use App\Modules\HotelProviders\V2\Shared\OnlinePurchaseExecutionResult;
use App\Modules\HotelProviders\V2\SnappTrip\Exceptions\SnappTripRateLimitExceeded;
use App\Modules\HotelProviders\V2\SnappTrip\Support\SnappTripBookingState;
use App\Modules\HotelProviders\V2\SnappTrip\Support\SnappTripSettings;
use App\Repositories\Contracts\AccommodationProviderMapRepositoryInterface;
use App\Repositories\Contracts\ReservationPurchaseRepositoryInterface;
use App\Repositories\Contracts\ReservationRepositoryInterface;
use App\Repositories\Contracts\RoomTypeProviderMapRepositoryInterface;
use App\Support\Reservation\PurchaseMethod;
use App\Support\Reservation\ReservationGuestService;
use App\Support\Reservation\ReservationGuestType;
use App\Support\Reservation\ReservationStatus;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use InvalidArgumentException;
use RuntimeException;

final readonly class SnappTripOnlinePurchaseHandler implements OnlinePurchaseHandler
{
    public function __construct(
        private SnappTripBookingService $booking,
        private ReservationPurchaseRepositoryInterface $purchases,
        private ReservationRepositoryInterface $reservations,
        private AccommodationProviderMapRepositoryInterface $accommodationMaps,
        private RoomTypeProviderMapRepositoryInterface $roomMaps,
    ) {
    }

    public function groupingKey(ReservationRoom $room): string
    {
        return 'room-type:'.($room->room_type_id ?? 'reservation-room-'.$room->id);
    }

    public function queue(): string
    {
        return 'snapptrip-operations';
    }

    public function execute(
        ReservationPurchase $purchase,
        int $attempt,
    ): OnlinePurchaseExecutionResult {
        if ((int) $purchase->purchase_mode !== PurchaseMethod::ONLINE) {
            return OnlinePurchaseExecutionResult::done();
        }

        if (in_array((int) $purchase->status, [
            ReservationStatus::ISSUE_SUCCESS,
            ReservationStatus::PAYMENT_REQUIRED,
        ], true)) {
            return OnlinePurchaseExecutionResult::done();
        }

        $reservation = $purchase->reservationHotel?->reservation;
        if ($reservation === null) {
            return $this->review($purchase, null, 'reservation_missing');
        }

        $reservationId = (int) $reservation->id;
        $settings = SnappTripSettings::from($purchase->provider);
        $pollSeconds = (int) data_get($settings, 'purchase.status_poll_seconds', 15);
        $pollAttempts = (int) data_get($settings, 'purchase.status_poll_attempts', 20);
        $reservationCode = trim((string) ($purchase->confirmation_code ?? ''));

        if (
            $reservationCode !== ''
            && $purchase->provider_status === 'price_increased'
        ) {
            return $this->review($purchase, $reservationId, 'price_increased');
        }

        try {
            if ($reservationCode === '') {
                $created = $this->booking->create(
                    $this->payload($purchase),
                    $reservationId,
                    $attempt,
                );

                $reservationCode = trim((string) ($created['reservation_code'] ?? ''));
                $purchaseAmount = $this->positiveAmount($created['price'] ?? null);
                $state = SnappTripBookingState::normalize($created['state'] ?? null);

                if ($reservationCode === '' || $purchaseAmount === null) {
                    return $this->review(
                        $purchase,
                        $reservationId,
                        $state ?? 'invalid_create_response',
                    );
                }

                // Persist the provider code before any later network call. If the job is
                // released or crashes after this point, the next attempt resumes this
                // exact booking instead of creating a duplicate provider reservation.
                $this->purchases->update((int) $purchase->id, [
                    'confirmation_code' => $reservationCode,
                    'purchase_amount' => $purchaseAmount,
                    'provider_status' => $state,
                ]);
                $purchase->forceFill([
                    'confirmation_code' => $reservationCode,
                    'purchase_amount' => $purchaseAmount,
                    'provider_status' => $state,
                ]);

                if ($this->priceIncreased($purchase, $purchaseAmount)) {
                    return $this->review($purchase, $reservationId, 'price_increased');
                }

                if (data_get($settings, 'purchase.lock_enabled') === true) {
                    $this->booking->lock($reservationCode, $reservationId, $attempt);
                }

                $booking = $this->booking->confirm(
                    $reservationCode,
                    $reservationId,
                    $attempt,
                );

                return $this->handleBookingState(
                    $purchase,
                    $booking,
                    $reservationId,
                    $attempt,
                    $pollAttempts,
                    $pollSeconds,
                    false,
                );
            }

            $booking = $this->booking->status(
                $reservationCode,
                $reservationId,
                $attempt,
            );

            return $this->handleBookingState(
                $purchase,
                $booking,
                $reservationId,
                $attempt,
                $pollAttempts,
                $pollSeconds,
                true,
            );
        } catch (SnappTripRateLimitExceeded $exception) {
            return OnlinePurchaseExecutionResult::retry($exception->retryAfterSeconds);
        } catch (ConnectionException $exception) {
            return $reservationCode === ''
                ? $this->review($purchase, $reservationId, 'create_connection_ambiguous')
                : OnlinePurchaseExecutionResult::retry($pollSeconds);
        } catch (RequestException $exception) {
            $status = $exception->response?->status();
            if ($status === 429) {
                return OnlinePurchaseExecutionResult::retry($pollSeconds);
            }

            if ($reservationCode !== '' && ($status === null || $status >= 500 || $status === 408)) {
                return OnlinePurchaseExecutionResult::retry($pollSeconds);
            }

            return $this->review(
                $purchase,
                $reservationId,
                $reservationCode === ''
                    ? 'create_http_'.($status ?? 'error')
                    : 'provider_http_'.($status ?? 'error'),
            );
        } catch (InvalidArgumentException|RuntimeException $exception) {
            return $this->review($purchase, $reservationId, 'configuration_or_mapping_error');
        }
    }

    public function prepareRetry(ReservationPurchase $purchase): array
    {
        $state = SnappTripBookingState::normalize($purchase->provider_status);
        $mustCreateAgain = $purchase->provider_status === 'price_increased'
            || SnappTripBookingState::isTerminalFailure($state)
            || trim((string) ($purchase->confirmation_code ?? '')) === '';

        if (!$mustCreateAgain) {
            return [
                'status' => ReservationStatus::BOOK_REQUESTED,
            ];
        }

        return [
            'status' => ReservationStatus::BOOK_REQUESTED,
            'confirmation_code' => null,
            'provider_status' => null,
            'purchase_amount' => null,
            'expires_at' => null,
            'issued_at' => null,
        ];
    }

    private function handleBookingState(
        ReservationPurchase $purchase,
        array $booking,
        int $reservationId,
        int $attempt,
        int $pollAttempts,
        int $pollSeconds,
        bool $mayNeedConfirm,
    ): OnlinePurchaseExecutionResult {
        $state = SnappTripBookingState::normalize($booking['state'] ?? null);
        $purchaseAmount = $this->positiveAmount($booking['price'] ?? null)
            ?? ($purchase->purchase_amount === null ? null : (int) $purchase->purchase_amount);

        if ($state !== null || $purchaseAmount !== null) {
            $updates = [];
            if ($state !== null) {
                $updates['provider_status'] = $state;
                $purchase->provider_status = $state;
            }
            if ($purchaseAmount !== null) {
                $updates['purchase_amount'] = $purchaseAmount;
                $purchase->purchase_amount = $purchaseAmount;
            }
            if ($updates !== []) {
                $this->purchases->update((int) $purchase->id, $updates);
            }
        }

        if (SnappTripBookingState::isConfirmed($state)) {
            if ($purchaseAmount === null) {
                return $this->review($purchase, $reservationId, 'confirmed_without_price');
            }

            return $this->issued($purchase, $reservationId, $purchaseAmount);
        }

        if ($mayNeedConfirm && SnappTripBookingState::needsConfirm($state)) {
            if ($purchaseAmount !== null && $this->priceIncreased($purchase, $purchaseAmount)) {
                return $this->review($purchase, $reservationId, 'price_increased');
            }

            $confirmed = $this->booking->confirm(
                (string) $purchase->confirmation_code,
                $reservationId,
                $attempt,
            );

            return $this->handleBookingState(
                $purchase,
                $confirmed,
                $reservationId,
                $attempt,
                $pollAttempts,
                $pollSeconds,
                false,
            );
        }

        if (SnappTripBookingState::isPending($state)) {
            if ($attempt >= $pollAttempts) {
                return $this->review($purchase, $reservationId, $state);
            }

            return OnlinePurchaseExecutionResult::retry($pollSeconds);
        }

        if (SnappTripBookingState::isTerminalFailure($state)) {
            return $this->review($purchase, $reservationId, $state);
        }

        return $this->review($purchase, $reservationId, $state ?? 'unknown_provider_state');
    }

    private function issued(
        ReservationPurchase $purchase,
        int $reservationId,
        int $purchaseAmount,
    ): OnlinePurchaseExecutionResult {
        $status = $this->priceIncreased($purchase, $purchaseAmount)
            ? ReservationStatus::PAYMENT_REQUIRED
            : ReservationStatus::ISSUE_SUCCESS;

        $this->purchases->update((int) $purchase->id, [
            'status' => $status,
            'purchase_amount' => $purchaseAmount,
            'provider_status' => SnappTripBookingState::CONFIRMED,
            'issued_at' => now(),
        ]);

        $this->reconcileReservation($reservationId);

        return OnlinePurchaseExecutionResult::done();
    }

    private function review(
        ReservationPurchase $purchase,
        ?int $reservationId,
        string $providerStatus,
    ): OnlinePurchaseExecutionResult {
        $this->purchases->update((int) $purchase->id, [
            'status' => ReservationStatus::UNDER_REVIEW,
            'provider_status' => $providerStatus,
        ]);

        if ($reservationId !== null) {
            $this->reservations->update($reservationId, [
                'status' => ReservationStatus::UNDER_REVIEW,
            ]);
        }

        return OnlinePurchaseExecutionResult::done();
    }

    private function reconcileReservation(int $reservationId): void
    {
        $reservation = $this->reservations->findForPurchase($reservationId);
        if ($reservation === null) {
            return;
        }

        $purchases = $reservation->hotels
            ->flatMap(fn ($hotel) => $hotel->purchases)
            ->unique('id')
            ->values();

        if ($purchases->isEmpty()) {
            return;
        }

        if ($purchases->contains(fn ($item): bool => (int) $item->status === ReservationStatus::UNDER_REVIEW)) {
            $status = ReservationStatus::UNDER_REVIEW;
        } elseif ($purchases->every(fn ($item): bool => in_array((int) $item->status, [
            ReservationStatus::ISSUE_SUCCESS,
            ReservationStatus::PAYMENT_REQUIRED,
        ], true))) {
            $status = $purchases->contains(
                fn ($item): bool => (int) $item->status === ReservationStatus::PAYMENT_REQUIRED
            )
                ? ReservationStatus::PAYMENT_REQUIRED
                : ReservationStatus::ISSUE_SUCCESS;
        } else {
            $status = ReservationStatus::BOOK_REQUESTED;
        }

        $this->reservations->update($reservationId, ['status' => $status]);
    }

    /** @return array<string,mixed> */
    private function payload(ReservationPurchase $purchase): array
    {
        $hotel = $purchase->reservationHotel;
        $reservation = $hotel?->reservation;
        $provider = $purchase->provider;

        if ($hotel === null || $reservation === null || $provider === null) {
            throw new InvalidArgumentException('Online purchase is missing reservation or provider context.');
        }

        $accommodationMap = $this->accommodationMaps->findForAccommodationAndProvider(
            (int) $hotel->accommodation_id,
            (int) $provider->id,
        );
        if ($accommodationMap === null || $accommodationMap->is_disabled) {
            throw new InvalidArgumentException('SnappTrip accommodation mapping is missing or disabled.');
        }

        $providerHotelId = trim((string) $accommodationMap->provider_property_id);
        if ($providerHotelId === '' || !ctype_digit($providerHotelId)) {
            throw new InvalidArgumentException('SnappTrip provider hotel id is invalid.');
        }

        if ($purchase->segments->isEmpty()) {
            throw new InvalidArgumentException('Online purchase has no room segment.');
        }

        $providerRoomId = null;
        $rooms = [];

        foreach ($purchase->segments as $segment) {
            $room = $segment->reservationRoom;
            if ($room === null || $room->room_type_id === null) {
                throw new InvalidArgumentException('Reservation room is missing its canonical room type.');
            }

            $roomMap = $this->roomMaps->findForRoomTypeAndAccommodationMap(
                (int) $room->room_type_id,
                (int) $accommodationMap->id,
            );
            if ($roomMap === null) {
                throw new InvalidArgumentException('SnappTrip room mapping is missing.');
            }

            $mappedRoomId = trim((string) $roomMap->provider_room_type_id);
            if ($mappedRoomId === '' || !ctype_digit($mappedRoomId)) {
                throw new InvalidArgumentException('SnappTrip provider room id is invalid.');
            }

            if ($providerRoomId !== null && $providerRoomId !== $mappedRoomId) {
                throw new InvalidArgumentException(
                    'SnappTrip requires all rooms in one booking request to have the same provider room id.'
                );
            }
            $providerRoomId = $mappedRoomId;

            $guests = $room->guests;
            if ($guests->isEmpty()) {
                throw new InvalidArgumentException('SnappTrip booking requires at least one guest per room.');
            }

            $rooms[] = [
                'room_id' => (int) $mappedRoomId,
                'extra_beds' => $this->extraBeds($room),
                'children' => $guests->where('type', ReservationGuestType::CHILD)->count(),
                'infants' => $guests->where('type', ReservationGuestType::INFANT)->count(),
                'guests' => $guests->map(fn (ReservationGuest $guest): array => [
                    'first_name' => trim((string) $guest->first_name),
                    'last_name' => trim((string) $guest->last_name),
                    'foreigner' => $this->isForeigner($guest),
                ])->values()->all(),
            ];
        }

        $phone = trim((string) $reservation->booker_mobile);
        if ($phone === '') {
            throw new InvalidArgumentException('SnappTrip booking requires the booker phone number.');
        }

        $payload = [
            'checkin' => $reservation->check_in?->format('Y-m-d'),
            'checkout' => $reservation->check_out?->format('Y-m-d'),
            'hotel_id' => (int) $providerHotelId,
            'phone' => $phone,
            'rooms' => $rooms,
        ];

        $email = trim((string) ($reservation->booker_email ?? ''));
        if ($email !== '') {
            $payload['email'] = $email;
        }

        return $payload;
    }

    private function extraBeds(ReservationRoom $room): int
    {
        $capacity = (int) ($room->roomType?->capacity ?? 0);
        if ($capacity < 1) {
            throw new InvalidArgumentException('Room capacity is required for SnappTrip booking.');
        }

        $adultCount = $room->guests->where('type', ReservationGuestType::ADULT)->count();
        $withServiceNonAdults = $room->guests
            ->filter(fn (ReservationGuest $guest): bool =>
                (int) $guest->type !== ReservationGuestType::ADULT
                && $guest->service === ReservationGuestService::WITH_SERVICE
            )
            ->count();

        $adultExtra = max(0, $adultCount - $capacity);
        $baseSlotsAfterAdults = max(0, $capacity - $adultCount);
        $baseWithService = min($withServiceNonAdults, $baseSlotsAfterAdults);
        $extraWithService = max(0, $withServiceNonAdults - $baseWithService);

        return $adultExtra + $extraWithService;
    }

    private function isForeigner(ReservationGuest $guest): bool
    {
        $country = $guest->country;
        $iso2 = strtoupper(trim((string) ($country?->iso2 ?? '')));
        $iso3 = strtoupper(trim((string) ($country?->iso3 ?? '')));

        if ($iso2 === '' && $iso3 === '') {
            throw new InvalidArgumentException('Guest country ISO code is required for SnappTrip booking.');
        }

        return $iso2 !== 'IR' && $iso3 !== 'IRN';
    }

    private function positiveAmount(mixed $value): ?int
    {
        return is_numeric($value) && (int) $value > 0 ? (int) $value : null;
    }

    private function priceIncreased(ReservationPurchase $purchase, int $purchaseAmount): bool
    {
        return $purchase->provider_quoted_amount !== null
            && $purchaseAmount > (int) $purchase->provider_quoted_amount;
    }
}
