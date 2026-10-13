<?php

namespace App\Modules\HotelProviders\V2\Shared;

use InvalidArgumentException;

final readonly class OnlinePurchasePreflightResult
{
    private function __construct(
        public bool $allowed,
        public ?int $manualReason = null,
        public ?string $manualReasonText = null,
    ) {
    }

    public static function allowed(): self
    {
        return new self(true);
    }

    public static function manual(int $manualReason, string $manualReasonText): self
    {
        if ($manualReason <= 0) {
            throw new InvalidArgumentException('Online purchase preflight manual reason must be a positive integer code.');
        }

        if (trim($manualReasonText) === '') {
            throw new InvalidArgumentException('Online purchase preflight manual reason text is required.');
        }

        return new self(false, $manualReason, $manualReasonText);
    }
}
