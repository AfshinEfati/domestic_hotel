<?php

namespace App\Repositories\Eloquent;

use App\Models\ProviderCityMap;
use App\Repositories\Contracts\ProviderCityMapRepositoryInterface;

class ProviderCityMapRepository extends BaseRepository implements ProviderCityMapRepositoryInterface
{
    public function __construct(ProviderCityMap $model)
    {
        parent::__construct($model);
    }

    /**
     * @return iterable<ProviderCityMap>
     */
    public function getAll(): iterable
    {
        /** @var iterable<ProviderCityMap> */
        return parent::getAll();
    }

    public function find(int|string $id): ?ProviderCityMap
    {
        /** @var ProviderCityMap|null */
        return parent::find($id);
    }

    public function store(array $data): ProviderCityMap
    {
        /** @var ProviderCityMap */
        return parent::store($data);
    }
}
