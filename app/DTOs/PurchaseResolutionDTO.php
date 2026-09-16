<?php

namespace App\DTOs;

use App\Support\Reservation\PurchaseManualReason;
use App\Support\Reservation\PurchaseMethod;

final readonly class PurchaseResolutionDTO
{
    public function __construct(
        public int $reservationId,
        public string $reservationNumber,
        public int $reservationHotelId,
        public int $providerId,
        public int $purchaseMode,
        public ?int $manualReason = null,
        public ?int $manualRuleId = null,
        public ?string $manualReasonText = null,
    ) {}

    public function toArray(): array
    {
        return [
            'reservation_id' => $this->reservationId,
            'reservation_number' => $this->reservationNumber,
            'reservation_hotel_id' => $this->reservationHotelId,
            'provider_id' => $this->providerId,
            'purchase_mode' => PurchaseMethod::options()[$this->purchaseMode],
            'manual_reason' => $this->manualReason === null
                ? null
                : PurchaseManualReason::get($this->manualReason),
            'manual_rule_id' => $this->manualRuleId,
            'manual_reason_text' => $this->manualReasonText,
        ];
    }
}
