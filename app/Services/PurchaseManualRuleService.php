<?php

namespace App\Services;

use App\DTOs\PurchaseManualRuleDTO;
use App\Models\PurchaseManualRule;
use App\Repositories\Contracts\PurchaseManualRuleRepositoryInterface;
use App\Services\Contracts\PurchaseManualRuleServiceInterface;
use Illuminate\Validation\ValidationException;

class PurchaseManualRuleService extends BaseService implements PurchaseManualRuleServiceInterface
{
    public function __construct(
        private readonly PurchaseManualRuleRepositoryInterface $purchaseManualRuleRepository
    ) {
        parent::__construct($purchaseManualRuleRepository);
    }

    public function store(mixed $payload): PurchaseManualRule
    {
        $data = $payload instanceof PurchaseManualRuleDTO
            ? $payload->toArray()
            : $this->normalisePayload($payload);

        $this->validateAmountRange($data);

        return $this->purchaseManualRuleRepository->store($data);
    }

    public function update(int|string $id, mixed $payload): bool
    {
        $data = $payload instanceof PurchaseManualRuleDTO
            ? $payload->toArray()
            : $this->normalisePayload($payload);

        $rule = $this->purchaseManualRuleRepository->find($id);

        if ($rule === null) {
            return false;
        }

        $this->validateAmountRange([
            'minimum_amount' => array_key_exists('minimum_amount', $data)
                ? $data['minimum_amount']
                : $rule->minimum_amount,
            'maximum_amount' => array_key_exists('maximum_amount', $data)
                ? $data['maximum_amount']
                : $rule->maximum_amount,
        ]);

        return $this->purchaseManualRuleRepository->update($id, $data);
    }

    private function validateAmountRange(array $data): void
    {
        $minimum = $data['minimum_amount'] ?? null;
        $maximum = $data['maximum_amount'] ?? null;

        if ($minimum !== null && $maximum !== null && (int) $maximum < (int) $minimum) {
            throw ValidationException::withMessages([
                'maximum_amount' => ['The maximum amount must be greater than or equal to the minimum amount.'],
            ]);
        }
    }
}
