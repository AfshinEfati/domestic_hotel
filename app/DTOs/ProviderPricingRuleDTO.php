<?php

namespace App\DTOs;

use Illuminate\Http\Request;

class ProviderPricingRuleDTO
{
    public function __construct(
        public readonly mixed $provider_id = null,
        public readonly mixed $percentage = null,
        public readonly mixed $fixed_amount = null,
        public readonly mixed $is_active = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            provider_id: $request->input('provider_id'),
            percentage: $request->input('percentage'),
            fixed_amount: $request->input('fixed_amount'),
            is_active: $request->input('is_active'),
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'provider_id' => $this->provider_id,
            'percentage' => $this->percentage,
            'fixed_amount' => $this->fixed_amount,
            'is_active' => $this->is_active,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
