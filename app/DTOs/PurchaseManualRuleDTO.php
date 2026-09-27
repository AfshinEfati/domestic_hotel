<?php

namespace App\DTOs;

use Illuminate\Http\Request;

final readonly class PurchaseManualRuleDTO
{
    public function __construct(private array $data) {}

    public static function fromRequest(Request $request): self
    {
        $fields = [
            'name',
            'provider_id',
            'accommodation_id',
            'minimum_amount',
            'maximum_amount',
            'start_time',
            'end_time',
            'is_active',
        ];

        $data = [];

        foreach ($fields as $field) {
            if ($request->exists($field)) {
                $data[$field] = $request->input($field);
            }
        }

        return new self($data);
    }

    public function toArray(): array
    {
        return $this->data;
    }
}
