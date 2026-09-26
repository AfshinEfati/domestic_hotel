<?php

namespace App\Services\Contracts;

use App\DTOs\PurchaseResolutionDTO;

interface PurchaseResolverInterface
{
    /** Resolve the purchase mode without persisting a decision. */
    public function resolve(int $reservationId, int $providerId, ?int $orderAmount = null): PurchaseResolutionDTO;

    /** Resolve and persist an offline decision when purchase processing is committed. */
    public function resolveAndRecord(int $reservationId, int $providerId, ?int $orderAmount = null): PurchaseResolutionDTO;
}
