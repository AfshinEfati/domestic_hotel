<?php

namespace App\Services\Contracts;

use App\DTOs\PurchaseResolutionDTO;

interface PurchaseResolverInterface
{
    /** Resolve the purchase mode without persisting a decision. */
    public function resolve(string $reservationNumber, int $providerId, ?int $orderAmount = null): PurchaseResolutionDTO;

    /** Resolve and persist an offline decision when purchase processing is committed. */
    public function resolveAndRecord(string $reservationNumber, int $providerId, ?int $orderAmount = null): PurchaseResolutionDTO;
}
