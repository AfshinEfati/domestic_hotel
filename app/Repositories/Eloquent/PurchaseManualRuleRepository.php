<?php

namespace App\Repositories\Eloquent;

use App\Models\PurchaseManualRule;
use App\Repositories\Contracts\PurchaseManualRuleRepositoryInterface;

class PurchaseManualRuleRepository extends BaseRepository implements PurchaseManualRuleRepositoryInterface
{
    public function __construct(PurchaseManualRule $model)
    {
        parent::__construct($model);
    }

    public function findMatching(
        int $providerId,
        int $accommodationId,
        int $saleAmount,
        string $localTime
    ): ?PurchaseManualRule {
        $rules = $this->model->newQuery()
            ->where('is_active', true)
            ->where(function ($query) use ($providerId): void {
                $query->whereNull('provider_id')->orWhere('provider_id', $providerId);
            })
            ->where(function ($query) use ($accommodationId): void {
                $query->whereNull('accommodation_id')->orWhere('accommodation_id', $accommodationId);
            })
            ->where(function ($query) use ($saleAmount): void {
                $query->whereNull('minimum_amount')->orWhere('minimum_amount', '<=', $saleAmount);
            })
            ->where(function ($query) use ($saleAmount): void {
                $query->whereNull('maximum_amount')->orWhere('maximum_amount', '>=', $saleAmount);
            })
            ->get();

        return $rules
            ->filter(fn (PurchaseManualRule $rule): bool => $this->matchesTime($rule, $localTime))
            ->sort(function (PurchaseManualRule $left, PurchaseManualRule $right): int {
                // Hotel + provider, hotel, provider, global. Oldest ID breaks ties.
                $leftSpecificity = ($left->accommodation_id !== null ? 2 : 0)
                    + ($left->provider_id !== null ? 1 : 0);
                $rightSpecificity = ($right->accommodation_id !== null ? 2 : 0)
                    + ($right->provider_id !== null ? 1 : 0);

                return ($rightSpecificity <=> $leftSpecificity) ?: ($left->id <=> $right->id);
            })
            ->first();
    }

    private function matchesTime(PurchaseManualRule $rule, string $localTime): bool
    {
        $start = $rule->getRawOriginal('start_time');
        $end = $rule->getRawOriginal('end_time');
        $start = $start === null ? null : substr((string) $start, 0, 8);
        $end = $end === null ? null : substr((string) $end, 0, 8);

        if ($start !== null && $end !== null) {
            return $start <= $end
                ? $localTime >= $start && $localTime <= $end
                : $localTime >= $start || $localTime <= $end;
        }

        return ($start === null || $localTime >= $start)
            && ($end === null || $localTime <= $end);
    }
}
