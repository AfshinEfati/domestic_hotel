<?php

namespace App\Services;

use App\Repositories\Contracts\ReservationRepositoryInterface;
use App\Services\Contracts\ReservationReferenceGeneratorInterface;
use Illuminate\Support\Str;
use RuntimeException;

class ReservationReferenceGenerator implements ReservationReferenceGeneratorInterface
{
    private const MAX_ATTEMPTS = 10;

    public function __construct(
        private readonly ReservationRepositoryInterface $reservationRepository
    ) {}

    public function generate(): string
    {
        $prefix = trim((string) config('hotel.reservation.reference_prefix', 'DH'));
        $randomLength = max(6, (int) config('hotel.reservation.reference_random_length', 10));

        for ($attempt = 0; $attempt < self::MAX_ATTEMPTS; $attempt++) {
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
