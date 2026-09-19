<?php

namespace App\Repositories\Eloquent;

use App\Models\RatePlanProviderMap;
use App\Repositories\Contracts\RatePlanProviderMapRepositoryInterface;
use Illuminate\Support\Collection;

class RatePlanProviderMapRepository extends BaseRepository implements RatePlanProviderMapRepositoryInterface
{
    public function __construct(RatePlanProviderMap $model)
    {
        parent::__construct($model);
    }

    /** @return iterable<RatePlanProviderMap> */
    public function getAll(): iterable
    {
        /** @var iterable<RatePlanProviderMap> */
        return parent::getAll();
    }

    public function find(int|string $id): ?RatePlanProviderMap
    {
        /** @var RatePlanProviderMap|null */
        return parent::find($id);
    }

    public function mappedForProviderIds(int $providerId, array $providerRateIds): Collection
    {
        if ($providerRateIds === []) {
            return collect();
        }

        return $this->model->newQuery()
            ->where('provider_id', $providerId)
            ->whereIn('provider_rate_plan_id', $providerRateIds)
            ->get()
            ->keyBy('provider_rate_plan_id');
    }

    public function store(array $data): RatePlanProviderMap
    {
        /** @var RatePlanProviderMap */
        return parent::store($data);
    }
}
