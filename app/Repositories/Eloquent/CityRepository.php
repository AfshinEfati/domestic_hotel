<?php

namespace App\Repositories\Eloquent;

use App\Models\City;
use App\Repositories\Contracts\CityRepositoryInterface;

class CityRepository extends BaseRepository implements CityRepositoryInterface
{
    public function __construct(City $model)
    {
        parent::__construct($model);
    }

    /**
     * @return iterable<City>
     */
    public function getAll(): iterable
    {
        /** @var iterable<City> */
        return parent::getAll();
    }

    public function find(int|string $id): ?City
    {
        /** @var City|null */
        return parent::find($id);
    }

    public function store(array $data): City
    {
        /** @var City */
        return parent::store($data);
    }
}
