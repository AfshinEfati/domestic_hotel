<?php

namespace App\Services\Contracts;

use App\DTOs\PurchaseResolutionDTO;

interface PurchaseResolverInterface
{
    public function resolve(string $reservationNumber, int $providerId): PurchaseResolutionDTO;
}
