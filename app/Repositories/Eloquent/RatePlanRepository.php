<?php

namespace App\Repositories\Eloquent;

use App\Models\RatePlan;
use App\Repositories\Contracts\RatePlanRepositoryInterface;

class RatePlanRepository extends BaseRepository implements RatePlanRepositoryInterface
{
    public function __construct(RatePlan $model)
    {
        parent::__construct($model);
    }

    /**
     * @return iterable<RatePlan>
     */
    public function getAll(): iterable
    {
        /** @var iterable<RatePlan> */
        return parent::getAll();
    }

    public function find(int|string $id): ?RatePlan
    {
        /** @var RatePlan|null */
        return parent::find($id);
    }

    public function store(array $data): RatePlan
    {
        /** @var RatePlan */
        return parent::store($data);
    }
}
