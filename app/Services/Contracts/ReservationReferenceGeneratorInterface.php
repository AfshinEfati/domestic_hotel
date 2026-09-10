<?php

namespace App\Services\Contracts;

interface ReservationReferenceGeneratorInterface
{
    public function generate(): string;
}
