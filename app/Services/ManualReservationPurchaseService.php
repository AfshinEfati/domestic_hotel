<?php

namespace App\Services;

use App\Models\Provider;
use App\Models\Reservation;
use App\Models\ReservationHotel;
use App\Models\ReservationPurchase;
use App\Models\ReservationRoom;
use App\Repositories\Contracts\AccommodationProviderMapRepositoryInterface;
use App\Repositories\Contracts\ProviderRepositoryInterface;
use App\Repositories\Contracts\RatePlanRepositoryInterface;
use App\Repositories\Contracts\ReservationGuestRepositoryInterface;
use App\Repositories\Contracts\ReservationHotelRepositoryInterface;
use App\Repositories\Contracts\ReservationManualPurchaseRepositoryInterface;
use App\Repositories\Contracts\ReservationPurchasePaymentRepositoryInterface;
use App\Repositories\Contracts\ReservationPurchaseRepositoryInterface;
use App\Repositories\Contracts\ReservationPurchaseSegmentRepositoryInterface;
use App\Repositories\Contracts\ReservationRepositoryInterface;
use App\Repositories\Contracts\ReservationRoomRepositoryInterface;
use App\Repositories\Contracts\RoomTypeRepositoryInterface;
use App\Services\Contracts\ManualReservationPurchaseServiceInterface;
use App\Services\Contracts\ProviderServiceInterface;
use App\Support\Provider\ProviderType;
use App\Support\Reservation\PaymentSource;
use App\Support\Reservation\PurchaseMethod;
use App\Support\Reservation\ReservationHotelType;
use App\Support\Reservation\ReservationRoomType;
use App\Support\Reservation\ReservationStatus;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final readonly class ManualReservationPurchaseService implements ManualReservationPurchaseServiceInterface
{
    public function __construct(
        private ReservationRepositoryInterface $reservations,
        private ReservationHotelRepositoryInterface $hotels,
        private ReservationRoomRepositoryInterface $rooms,
        private ReservationGuestRepositoryInterface $guests,
        private ReservationPurchaseRepositoryInterface $purchases,
        private ReservationManualPurchaseRepositoryInterface $manualPurchases,
        private ReservationPurchaseSegmentRepositoryInterface $segments,
        private ReservationPurchasePaymentRepositoryInterface $payments,
        private ProviderRepositoryInterface $providers,
        private ProviderServiceInterface $providerService,
        private AccommodationProviderMapRepositoryInterface $providerMaps,
        private RoomTypeRepositoryInterface $roomTypes,
        private RatePlanRepositoryInterface $ratePlans,
    ) {}

    public function show(int $reservationId): Reservation
    {
        return $this->reload($reservationId);
    }

    public function update(int $reservationId, array $payload): Reservation
    {
        return DB::transaction(function () use ($reservationId, $payload): Reservation {
            $reservation = $this->lock($reservationId);
            $this->assertEditable($reservation);

            $accCode = $this->requiredAccCode($payload);

            if (isset($payload['hotel']) && is_array($payload['hotel'])) {
                $hotelPayload = $payload['hotel'];
                $hotelPayload['acc_code'] = $accCode;
                $this->applyHotel($reservation, $hotelPayload);
                $reservation = $this->lock($reservationId);
            }

            foreach (($payload['rooms'] ?? []) as $roomPayload) {
                if (!is_array($roomPayload)) {
                    continue;
                }

                $roomPayload['acc_code'] = $accCode;
                $this->applyRoom($reservation, $roomPayload);
                $reservation = $this->lock($reservationId);
            }

            foreach (($payload['purchases'] ?? []) as $purchasePayload) {
                if (!is_array($purchasePayload) || !isset($purchasePayload['id'])) {
                    throw new InvalidArgumentException('Each purchase update requires its purchase id.');
                }

                $purchasePayload['acc_code'] = $accCode;
                $this->applyPurchase(
                    $reservation,
                    (int) $purchasePayload['id'],
                    $purchasePayload
                );

                foreach (($purchasePayload['payments'] ?? []) as $paymentPayload) {
                    if (!is_array($paymentPayload)) {
                        continue;
                    }

                    $paymentPayload['acc_code'] = $accCode;
                    $this->applyPayment(
                        $reservation,
                        (int) $purchasePayload['id'],
                        $paymentPayload,
                        false,
                    );
                }

                $reservation = $this->lock($reservationId);
            }

            $this->markUnderReview($reservation, $accCode);

            return $this->reload($reservationId);
        });
    }

    public function addHotel(int $reservationId, array $payload): Reservation
    {
        return DB::transaction(function () use ($reservationId, $payload): Reservation {
            $reservation = $this->lock($reservationId);
            $this->assertEditable($reservation);

            $accCode = $this->requiredAccCode($payload);
            $this->applyHotel($reservation, $payload);
            $this->markUnderReview($reservation, $accCode);

            return $this->reload($reservationId);
        });
    }

    public function selectHotel(int $reservationId, int $reservationHotelId, string $accCode): Reservation
    {
        return DB::transaction(function () use ($reservationId, $reservationHotelId, $accCode): Reservation {
            $reservation = $this->lock($reservationId);
            $this->assertEditable($reservation);

            $hotel = $this->hotels->findForReservation($reservationId, $reservationHotelId);
            if ($hotel === null) {
                throw new InvalidArgumentException('Reservation hotel was not found.');
            }

            $this->selectHotelModel($reservation, $hotel);
            $this->markUnderReview($reservation, $accCode);

            return $this->reload($reservationId);
        });
    }

    public function addRoom(int $reservationId, array $payload): Reservation
    {
        return DB::transaction(function () use ($reservationId, $payload): Reservation {
            $reservation = $this->lock($reservationId);
            $this->assertEditable($reservation);

            $accCode = $this->requiredAccCode($payload);
            $this->applyRoom($reservation, $payload);
            $this->markUnderReview($reservation, $accCode);

            return $this->reload($reservationId);
        });
    }

    public function selectRoom(int $reservationId, int $reservationRoomId, string $accCode): Reservation
    {
        return DB::transaction(function () use ($reservationId, $reservationRoomId, $accCode): Reservation {
            $reservation = $this->lock($reservationId);
            $this->assertEditable($reservation);

            $room = $this->findRoomForReservation($reservation, $reservationRoomId);
            $this->selectRoomModel($reservation, $room);
            $this->markUnderReview($reservation, $accCode);

            return $this->reload($reservationId);
        });
    }

    public function assignGuests(int $reservationId, array $payload): Reservation
    {
        return DB::transaction(function () use ($reservationId, $payload): Reservation {
            $reservation = $this->lock($reservationId);
            $this->assertEditable($reservation);

            $accCode = $this->requiredAccCode($payload);
            $assignments = $payload['rooms'] ?? [];

            $seen = [];
            foreach ($assignments as $assignment) {
                $roomId = (int) ($assignment['room_id'] ?? 0);
                $room = $this->findRoomForReservation($reservation, $roomId);

                foreach (($assignment['guest_ids'] ?? []) as $guestId) {
                    $guestId = (int) $guestId;

                    if (isset($seen[$guestId])) {
                        throw new InvalidArgumentException('A guest cannot be assigned to more than one room.');
                    }

                    if ($this->guests->findForReservation($reservationId, $guestId) === null) {
                        throw new InvalidArgumentException("Guest {$guestId} does not belong to this reservation.");
                    }

                    $seen[$guestId] = true;
                    $this->guests->assignToRoom($guestId, (int) $room->id);
                }
            }

            $allGuestIds = $this->guests->getIdsForReservation($reservationId);
            sort($allGuestIds);
            $assignedIds = array_map('intval', array_keys($seen));
            sort($assignedIds);

            if ($allGuestIds !== $assignedIds) {
                throw new InvalidArgumentException(
                    'Guest assignment must include every reservation guest exactly once.'
                );
            }

            $this->markUnderReview($reservation, $accCode);

            return $this->reload($reservationId);
        });
    }

    public function updatePurchase(int $reservationId, int $purchaseId, array $payload): Reservation
    {
        return DB::transaction(function () use ($reservationId, $purchaseId, $payload): Reservation {
            $reservation = $this->lock($reservationId);
            $this->assertEditable($reservation);

            $accCode = $this->requiredAccCode($payload);
            $this->applyPurchase($reservation, $purchaseId, $payload);
            $this->markUnderReview($reservation, $accCode);

            return $this->reload($reservationId);
        });
    }

    public function addPayment(int $reservationId, int $purchaseId, array $payload): Reservation
    {
        return DB::transaction(function () use ($reservationId, $purchaseId, $payload): Reservation {
            $reservation = $this->lock($reservationId);
            $this->assertEditable($reservation);

            $accCode = $this->requiredAccCode($payload);
            $this->applyPayment($reservation, $purchaseId, $payload, true);

            if ((int) $reservation->status === ReservationStatus::BOOK_REQUESTED) {
                $this->markUnderReview($reservation, $accCode);
            }

            return $this->reload($reservationId);
        });
    }

    public function confirm(int $reservationId, array $payload): Reservation
    {
        return DB::transaction(function () use ($reservationId, $payload): Reservation {
            $reservation = $this->lock($reservationId);
            $this->assertEditable($reservation);

            $accCode = $this->requiredAccCode($payload);
            $finalHotel = $this->requiredFinalHotel($reservation);
            $finalRooms = collect($this->rooms->getFinalByHotel((int) $finalHotel->id));

            if ($finalRooms->isEmpty()) {
                throw new InvalidArgumentException('Final hotel must have at least one final room.');
            }

            $this->assertAllGuestsOnFinalRooms($reservationId, $finalRooms);

            $currentPurchases = $this->reservationPurchases($reservation)
                ->filter(fn (ReservationPurchase $purchase): bool =>
                    (int) $purchase->reservation_hotel_id === (int) $finalHotel->id
                )
                ->values();

            if ($currentPurchases->isEmpty()) {
                throw new InvalidArgumentException('Reservation has no purchase to confirm.');
            }

            if ($currentPurchases->count() === 1) {
                /** @var ReservationPurchase $singlePurchase */
                $singlePurchase = $currentPurchases->first();
                $segmentRoomIds = $singlePurchase->segments
                    ->pluck('reservation_room_id')
                    ->map(fn ($id) => (int) $id)
                    ->sort()
                    ->values()
                    ->all();

                $finalRoomIds = $finalRooms
                    ->pluck('id')
                    ->map(fn ($id) => (int) $id)
                    ->sort()
                    ->values()
                    ->all();

                if ($segmentRoomIds !== $finalRoomIds) {
                    $this->rebuildSegments(
                        $reservation,
                        $singlePurchase,
                        $finalRoomIds,
                        $finalHotel,
                    );
                }
            } else {
                $this->assertFinalRoomsCoveredExactlyOnce($finalRooms, $currentPurchases);
            }

            $hasOutstandingPayment = false;

            foreach ($currentPurchases as $purchase) {
                $purchase = $this->purchases->findForReservation($reservationId, (int) $purchase->id)
                    ?? throw new InvalidArgumentException('Reservation purchase was not found.');

                if ($purchase->purchase_amount === null || (int) $purchase->purchase_amount <= 0) {
                    throw new InvalidArgumentException(
                        "Purchase {$purchase->id} requires a positive purchase_amount before confirmation."
                    );
                }

                $this->assertProviderMatchesHotel($purchase->provider, $finalHotel);

                $paid = $this->payments->sumPaidForPurchase((int) $purchase->id);
                $purchaseAmount = (int) $purchase->purchase_amount;
                $status = $paid >= $purchaseAmount
                    ? ReservationStatus::ISSUE_SUCCESS
                    : ReservationStatus::PAYMENT_REQUIRED;

                if ($status === ReservationStatus::PAYMENT_REQUIRED) {
                    $hasOutstandingPayment = true;
                }

                $this->purchases->update($purchase->id, [
                    'status' => $status,
                    'reservation_hotel_id' => $finalHotel->id,
                    'issued_at' => now(),
                ]);

                $manualData = [
                    'acc_code' => $accCode,
                    'purchased_at' => now(),
                ];

                if (array_key_exists('description', $payload)) {
                    $manualData['description'] = $payload['description'];
                }

                $this->manualPurchases->updateForPurchase((int) $purchase->id, $manualData);
            }

            $this->reservations->update($reservationId, [
                'status' => $hasOutstandingPayment
                    ? ReservationStatus::PAYMENT_REQUIRED
                    : ReservationStatus::ISSUE_SUCCESS,
            ]);

            return $this->reload($reservationId);
        });
    }

    public function reject(int $reservationId, array $payload): Reservation
    {
        return DB::transaction(function () use ($reservationId, $payload): Reservation {
            $reservation = $this->lock($reservationId);
            $accCode = $this->requiredAccCode($payload);

            foreach ($this->reservationPurchases($reservation) as $purchase) {
                $this->purchases->update($purchase->id, [
                    'status' => ReservationStatus::ISSUE_FAILED,
                ]);

                $manualData = ['acc_code' => $accCode];
                if (array_key_exists('description', $payload)) {
                    $manualData['description'] = $payload['description'];
                }

                $this->manualPurchases->updateForPurchase((int) $purchase->id, $manualData);
            }

            $this->reservations->update($reservationId, [
                'status' => ReservationStatus::ISSUE_FAILED,
            ]);

            return $this->reload($reservationId);
        });
    }

    private function applyHotel(Reservation $reservation, array $payload): ReservationHotel
    {
        $accommodationId = (int) ($payload['accommodation_id'] ?? 0);
        if ($accommodationId <= 0) {
            throw new InvalidArgumentException('accommodation_id is required.');
        }

        $hotel = $this->hotels->findByReservationAndAccommodation(
            (int) $reservation->id,
            $accommodationId
        );

        if ($hotel === null) {
            $hotel = $this->hotels->store([
                'reservation_id' => $reservation->id,
                'accommodation_id' => $accommodationId,
                'type' => ReservationHotelType::ALTERNATIVE,
                'is_final' => false,
            ]);
        }

        if ((bool) ($payload['select'] ?? true)) {
            $this->selectHotelModel($reservation, $hotel);
        }

        return $hotel;
    }

    private function selectHotelModel(Reservation $reservation, ReservationHotel $hotel): void
    {
        $this->hotels->clearFinalByReservation((int) $reservation->id);
        $this->hotels->markFinal((int) $hotel->id);

        foreach ($this->reservationPurchases($reservation) as $purchase) {
            $this->segments->deleteForPurchase((int) $purchase->id);
            $this->purchases->update($purchase->id, [
                'reservation_hotel_id' => $hotel->id,
            ]);
        }
    }

    private function applyRoom(Reservation $reservation, array $payload): ReservationRoom
    {
        $hotelId = isset($payload['reservation_hotel_id'])
            ? (int) $payload['reservation_hotel_id']
            : (int) $this->requiredFinalHotel($reservation)->id;

        $hotel = $this->hotels->findForReservation((int) $reservation->id, $hotelId);
        if ($hotel === null) {
            throw new InvalidArgumentException('Reservation hotel was not found.');
        }

        $roomNumber = (int) ($payload['room_number'] ?? 0);
        if ($roomNumber <= 0) {
            throw new InvalidArgumentException('room_number is required.');
        }

        $roomTypeId = isset($payload['room_type_id']) ? (int) $payload['room_type_id'] : null;
        $roomName = isset($payload['room_name']) ? trim((string) $payload['room_name']) : null;
        $ratePlanId = isset($payload['rate_plan_id']) ? (int) $payload['rate_plan_id'] : null;

        if ($roomTypeId === null && ($roomName === null || $roomName === '')) {
            throw new InvalidArgumentException('room_type_id or room_name is required.');
        }

        if ($roomTypeId !== null) {
            $roomType = $this->roomTypes->find($roomTypeId);
            if ($roomType === null || (int) $roomType->accommodation_id !== (int) $hotel->accommodation_id) {
                throw new InvalidArgumentException('Selected room type does not belong to the reservation hotel.');
            }

            $roomName ??= $roomType->fa_name;
        }

        if ($ratePlanId !== null) {
            $ratePlan = $this->ratePlans->find($ratePlanId);
            if ($ratePlan === null || (int) $ratePlan->accommodation_id !== (int) $hotel->accommodation_id) {
                throw new InvalidArgumentException('Selected rate plan does not belong to the reservation hotel.');
            }
        }

        $room = $this->rooms->findByHotelNumberAndType(
            (int) $hotel->id,
            $roomNumber,
            ReservationRoomType::ALTERNATIVE
        );

        $roomData = [
            'reservation_hotel_id' => $hotel->id,
            'room_number' => $roomNumber,
            'type' => ReservationRoomType::ALTERNATIVE,
            'is_final' => false,
            'room_type_id' => $roomTypeId,
            'rate_plan_id' => $ratePlanId,
            'room_name' => $roomName,
            'room_calendar_id' => null,
            'provider_id' => null,
            'initial_price' => null,
            'validated_price' => null,
        ];

        if ($room === null) {
            $room = $this->rooms->store($roomData);
        } else {
            $this->rooms->update($room->id, $roomData);
            $room->refresh();
        }

        if ((bool) ($payload['select'] ?? true)) {
            $this->selectRoomModel($reservation, $room);
        }

        foreach (($payload['guest_ids'] ?? []) as $guestId) {
            $guestId = (int) $guestId;

            if ($this->guests->findForReservation((int) $reservation->id, $guestId) === null) {
                throw new InvalidArgumentException("Guest {$guestId} does not belong to this reservation.");
            }

            $this->guests->assignToRoom($guestId, (int) $room->id);
        }

        return $room;
    }

    private function selectRoomModel(Reservation $reservation, ReservationRoom $room): void
    {
        $this->rooms->clearFinalByHotelAndNumber(
            (int) $room->reservation_hotel_id,
            (int) $room->room_number
        );

        $this->rooms->update($room->id, ['is_final' => true]);

        foreach ($this->reservationPurchases($reservation) as $purchase) {
            if ((int) $purchase->reservation_hotel_id === (int) $room->reservation_hotel_id) {
                $this->segments->deleteForPurchase((int) $purchase->id);
            }
        }
    }

    private function applyPurchase(
        Reservation $reservation,
        int $purchaseId,
        array $payload
    ): ReservationPurchase {
        $purchase = $this->purchases->findForReservation((int) $reservation->id, $purchaseId);
        if ($purchase === null) {
            throw new InvalidArgumentException('Reservation purchase was not found.');
        }

        $finalHotel = $this->requiredFinalHotel($reservation);
        $updates = [
            'reservation_hotel_id' => $finalHotel->id,
            'purchase_mode' => PurchaseMethod::OFFLINE,
        ];

        if (
            (bool) ($payload['use_hotel_as_provider'] ?? false)
            && array_key_exists('provider_id', $payload)
            && $payload['provider_id'] !== null
        ) {
            throw new InvalidArgumentException(
                'provider_id and use_hotel_as_provider cannot be used together.'
            );
        }

        if ((bool) ($payload['use_hotel_as_provider'] ?? false)) {
            $hotelProvider = $this->providerService->storeOfflineByAccommodationId(
                (int) $finalHotel->accommodation_id
            );
            $updates['provider_id'] = $hotelProvider->id;
        } elseif (array_key_exists('provider_id', $payload) && $payload['provider_id'] !== null) {
            $provider = $this->providers->find((int) $payload['provider_id']);
            if ($provider === null) {
                throw new InvalidArgumentException('Selected provider was not found.');
            }

            $this->assertProviderMatchesHotel($provider, $finalHotel);
            $updates['provider_id'] = $provider->id;
        }

        if (array_key_exists('purchase_amount', $payload)) {
            $updates['purchase_amount'] = $payload['purchase_amount'] === null
                ? null
                : (int) $payload['purchase_amount'];
        }

        if (array_key_exists('confirmation_code', $payload)) {
            $updates['confirmation_code'] = $payload['confirmation_code'];
        }

        $this->purchases->update($purchase->id, $updates);

        $manualData = [
            'acc_code' => $this->requiredAccCode($payload),
        ];

        if (array_key_exists('description', $payload)) {
            $manualData['description'] = $payload['description'];
        }

        $this->manualPurchases->updateForPurchase((int) $purchase->id, $manualData);

        $purchase = $this->purchases->findForReservation((int) $reservation->id, $purchaseId)
            ?? throw new InvalidArgumentException('Reservation purchase was not found after update.');

        if (array_key_exists('room_ids', $payload)) {
            $roomIds = array_values(array_unique(array_map('intval', $payload['room_ids'] ?? [])));
            $this->rebuildSegments($reservation, $purchase, $roomIds, $finalHotel);
        }

        return $purchase;
    }

    private function applyPayment(
        Reservation $reservation,
        int $purchaseId,
        array $payload,
        bool $reconcileStatus
    ): void {
        $purchase = $this->purchases->findForReservation((int) $reservation->id, $purchaseId);
        if ($purchase === null) {
            throw new InvalidArgumentException('Reservation purchase was not found.');
        }

        $source = (int) ($payload['source'] ?? 0);
        if (!PaymentSource::isValidManualPurchaseSource($source)) {
            throw new InvalidArgumentException('Invalid manual purchase payment source.');
        }

        $amount = (int) ($payload['amount'] ?? 0);
        if ($amount <= 0) {
            throw new InvalidArgumentException('Payment amount must be greater than zero.');
        }

        if (empty($payload['paid_at'])) {
            throw new InvalidArgumentException('paid_at is required.');
        }

        $this->payments->store([
            'reservation_purchase_id' => $purchase->id,
            'amount' => $amount,
            'source' => $source,
            'paid_at' => $payload['paid_at'],
        ]);

        $this->manualPurchases->updateForPurchase((int) $purchase->id, [
            'acc_code' => $this->requiredAccCode($payload),
        ]);

        if ($reconcileStatus) {
            $this->reconcilePaymentStatus($reservation, $purchase);
        }
    }

    private function rebuildSegments(
        Reservation $reservation,
        ReservationPurchase $purchase,
        array $roomIds,
        ReservationHotel $finalHotel
    ): void {
        $this->segments->deleteForPurchase((int) $purchase->id);

        foreach ($roomIds as $roomId) {
            $room = $this->rooms->findForReservationHotel((int) $finalHotel->id, (int) $roomId);

            if ($room === null || !$room->is_final) {
                throw new InvalidArgumentException(
                    "Room {$roomId} is not a final room of the selected hotel."
                );
            }

            $this->segments->firstOrCreateForPurchaseRoom(
                (int) $purchase->id,
                (int) $room->id,
                $reservation->check_in->format('Y-m-d'),
                $reservation->check_out->format('Y-m-d'),
            );

            $this->rooms->update($room->id, [
                'provider_id' => $purchase->provider_id,
            ]);
        }
    }

    private function reconcilePaymentStatus(
        Reservation $reservation,
        ReservationPurchase $purchase
    ): void {
        if ($purchase->purchase_amount === null) {
            return;
        }

        $paid = $this->payments->sumPaidForPurchase((int) $purchase->id);
        if ($paid < (int) $purchase->purchase_amount) {
            return;
        }

        if ((int) $purchase->status === ReservationStatus::PAYMENT_REQUIRED) {
            $this->purchases->update($purchase->id, [
                'status' => ReservationStatus::ISSUE_SUCCESS,
            ]);
        }

        $fresh = $this->reload((int) $reservation->id);
        $statuses = $this->reservationPurchases($fresh)
            ->pluck('status')
            ->map(fn ($status) => (int) $status);

        if ($statuses->isNotEmpty() && $statuses->every(
            fn (int $status): bool => $status === ReservationStatus::ISSUE_SUCCESS
        )) {
            $this->reservations->update($reservation->id, [
                'status' => ReservationStatus::ISSUE_SUCCESS,
            ]);
        }
    }

    private function assertAllGuestsOnFinalRooms(int $reservationId, Collection $finalRooms): void
    {
        $allGuestIds = $this->guests->getIdsForReservation($reservationId);
        sort($allGuestIds);

        $finalRoomIds = $finalRooms
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $assigned = [];
        foreach ($allGuestIds as $guestId) {
            $guest = $this->guests->findForReservation($reservationId, $guestId);
            if ($guest === null || !in_array((int) $guest->reservation_room_id, $finalRoomIds, true)) {
                throw new InvalidArgumentException(
                    'Every reservation guest must be assigned to a final room before confirmation.'
                );
            }

            $assigned[] = (int) $guest->id;
        }

        sort($assigned);
        if ($assigned !== $allGuestIds) {
            throw new InvalidArgumentException('Invalid final guest assignment.');
        }
    }

    private function assertFinalRoomsCoveredExactlyOnce(
        Collection $finalRooms,
        Collection $purchases
    ): void {
        $expected = $finalRooms
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->sort()
            ->values()
            ->all();

        $actual = $purchases
            ->flatMap(fn (ReservationPurchase $purchase) =>
                $purchase->segments->pluck('reservation_room_id')
            )
            ->map(fn ($id) => (int) $id)
            ->all();

        if (count($actual) !== count(array_unique($actual))) {
            throw new InvalidArgumentException('A final room cannot belong to more than one purchase.');
        }

        sort($actual);

        if ($expected !== $actual) {
            throw new InvalidArgumentException(
                'Every final room must be assigned to exactly one purchase before confirmation.'
            );
        }
    }

    private function assertProviderMatchesHotel(?Provider $provider, ReservationHotel $hotel): void
    {
        if ($provider === null) {
            throw new InvalidArgumentException('Purchase provider is required.');
        }

        if ((int) $provider->provider_type === ProviderType::HOTEL_DIRECT) {
            if ((int) $provider->accommodation_id !== (int) $hotel->accommodation_id) {
                throw new InvalidArgumentException(
                    'Direct hotel provider does not belong to the selected hotel.'
                );
            }

            return;
        }

        if (!$this->providerMaps->existsForAccommodationAndProvider(
            (int) $hotel->accommodation_id,
            (int) $provider->id
        )) {
            throw new InvalidArgumentException(
                'Selected provider is not mapped to the selected hotel.'
            );
        }
    }

    private function markUnderReview(Reservation $reservation, string $accCode): void
    {
        $this->reservations->update($reservation->id, [
            'status' => ReservationStatus::UNDER_REVIEW,
        ]);

        foreach ($this->reservationPurchases($reservation) as $purchase) {
            $this->purchases->update($purchase->id, [
                'status' => ReservationStatus::UNDER_REVIEW,
                'purchase_mode' => PurchaseMethod::OFFLINE,
            ]);

            $this->manualPurchases->updateForPurchase((int) $purchase->id, [
                'acc_code' => $accCode,
            ]);
        }
    }

    private function reservationPurchases(Reservation $reservation): Collection
    {
        return $reservation->hotels
            ->flatMap(fn (ReservationHotel $hotel) => $hotel->purchases)
            ->unique('id')
            ->values();
    }

    private function requiredFinalHotel(Reservation $reservation): ReservationHotel
    {
        $finalHotels = $reservation->hotels->where('is_final', true);

        if ($finalHotels->count() !== 1) {
            throw new InvalidArgumentException('Reservation must have exactly one final hotel.');
        }

        /** @var ReservationHotel */
        return $finalHotels->first();
    }

    private function findRoomForReservation(
        Reservation $reservation,
        int $reservationRoomId
    ): ReservationRoom {
        foreach ($reservation->hotels as $hotel) {
            $room = $this->rooms->findForReservationHotel(
                (int) $hotel->id,
                $reservationRoomId
            );

            if ($room !== null) {
                return $room;
            }
        }

        throw new InvalidArgumentException('Reservation room was not found.');
    }

    private function requiredAccCode(array $payload): string
    {
        $accCode = trim((string) ($payload['acc_code'] ?? ''));

        if ($accCode === '') {
            throw new InvalidArgumentException('acc_code is required.');
        }

        return $accCode;
    }

    private function assertEditable(Reservation $reservation): void
    {
        if ((int) $reservation->status === ReservationStatus::ISSUE_FAILED) {
            throw new InvalidArgumentException('Rejected reservation cannot be edited.');
        }
    }

    private function lock(int $reservationId): Reservation
    {
        return $this->reservations->findForPurchase($reservationId, true)
            ?? throw (new ModelNotFoundException())->setModel(Reservation::class, [$reservationId]);
    }

    private function reload(int $reservationId): Reservation
    {
        return $this->reservations->findWithDetails($reservationId)
            ?? throw (new ModelNotFoundException())->setModel(Reservation::class, [$reservationId]);
    }
}
