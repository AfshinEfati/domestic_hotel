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
        for ($attempt = 0; $attempt < self::MAX_ATTEMPTS; $attempt++) {
            $reference = sprintf(
                'DH-%s-%s',
                now()->format('Ymd'),
                Str::upper(Str::random(10))
            );

            if ($this->reservationRepository->findByReservationNumber($reference) === null) {
                return $reference;
            }
        }

        throw new RuntimeException('Unable to generate a unique reservation number.');
    }
}
