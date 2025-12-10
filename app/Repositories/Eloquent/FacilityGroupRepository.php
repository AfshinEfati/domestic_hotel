<?php

namespace App\Repositories\Eloquent;

use App\Models\FacilityGroup;
use App\Repositories\Contracts\FacilityGroupRepositoryInterface;

class FacilityGroupRepository extends BaseRepository implements FacilityGroupRepositoryInterface
{
    public function __construct(FacilityGroup $model)
    {
        parent::__construct($model);
    }

    /**
     * @return iterable<FacilityGroup>
     */
    public function getAll(): iterable
    {
        /** @var iterable<FacilityGroup> */
        return parent::getAll();
    }

    public function find(int|string $id): ?FacilityGroup
    {
        /** @var FacilityGroup|null */
        return parent::find($id);
    }

    public function store(array $data): FacilityGroup
    {
        /** @var FacilityGroup */
        return parent::store($data);
    }
}
