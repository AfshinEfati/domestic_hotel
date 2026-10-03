<?php

namespace App\Domain\Hotel\Exceptions;

use RuntimeException;

final class InvalidProviderAvailabilityDataException extends RuntimeException
{
    /**
     * @param array<int, array<string, mixed>> $issues
     */
    public function __construct(public readonly array $issues)
    {
        parent::__construct(
            'Provider availability payload contains values that cannot be safely persisted.'
        );
    }
}
