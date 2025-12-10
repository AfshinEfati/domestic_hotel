<?php

namespace App\Repositories\Eloquent;

use App\Models\RatePlanProviderMap;
use App\Repositories\Contracts\RatePlanProviderMapRepositoryInterface;

class RatePlanProviderMapRepository extends BaseRepository implements RatePlanProviderMapRepositoryInterface
{
    public function __construct(RatePlanProviderMap $model)
    {
        parent::__construct($model);
    }

    /**
     * @return iterable<RatePlanProviderMap>
     */
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

    public function store(array $data): RatePlanProviderMap
    {
        /** @var RatePlanProviderMap */
        return parent::store($data);
    }
}
