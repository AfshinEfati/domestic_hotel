<?php

namespace App\DTOs;

use Illuminate\Http\Request;

final readonly class ManualReservationPurchaseDTO
{
    public function __construct(private array $data) {}

    public static function fromRequest(Request $request): self
    {
        return new self($request->validated());
    }

    public function toArray(): array
    {
        return $this->data;
    }
}
