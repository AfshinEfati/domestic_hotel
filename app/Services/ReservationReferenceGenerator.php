<?php

namespace App\Services;

use App\Repositories\Contracts\ReservationRepositoryInterface;
use App\Services\Contracts\HotelSettingServiceInterface;
use App\Services\Contracts\ReservationReferenceGeneratorInterface;
use App\Support\Hotel\HotelSettingKey;
use Illuminate\Support\Str;
use RuntimeException;

class ReservationReferenceGenerator implements ReservationReferenceGeneratorInterface
{
    public function __construct(
        private readonly ReservationRepositoryInterface $reservationRepository,
        private readonly HotelSettingServiceInterface $settingService,
    ) {}

    public function generate(): string
    {
        $prefix = trim((string) $this->settingService->getValue(
            HotelSettingKey::RESERVATION_REFERENCE_PREFIX
        ));
        $randomLength = (int) $this->settingService->getValue(
            HotelSettingKey::RESERVATION_REFERENCE_RANDOM_LENGTH
        );
        $maxAttempts = (int) $this->settingService->getValue(
            HotelSettingKey::RESERVATION_REFERENCE_MAX_ATTEMPTS
        );

        if ($randomLength < 6 || $randomLength > 64) {
            throw new RuntimeException('Reservation reference random length must be between 6 and 64.');
        }

        if ($maxAttempts < 1) {
            throw new RuntimeException('Reservation reference max attempts must be greater than zero.');
        }

        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            $randomPart = Str::upper(Str::random($randomLength));
            $datePart = now()->format('Ymd');

            $reference = $prefix !== ''
                ? sprintf('%s-%s-%s', $prefix, $datePart, $randomPart)
                : sprintf('%s-%s', $datePart, $randomPart);

            if (!$this->reservationRepository->existsByReservationNumber($reference)) {
                return $reference;
            }
        }

        throw new RuntimeException('Unable to generate a unique reservation number.');
    }
}
