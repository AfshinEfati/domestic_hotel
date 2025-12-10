<?php

namespace App\Repositories\Eloquent;

use App\Models\Country;
use App\Repositories\Contracts\CountryRepositoryInterface;

class CountryRepository extends BaseRepository implements CountryRepositoryInterface
{
    public function __construct(Country $model)
    {
        parent::__construct($model);
    }

    /**
     * @return iterable<Country>
     */
    public function getAll(): iterable
    {
        /** @var iterable<Country> */
        return parent::getAll();
    }

    public function find(int|string $id): ?Country
    {
        /** @var Country|null */
        return parent::find($id);
    }

    public function store(array $data): Country
    {
        /** @var Country */
        return parent::store($data);
    }
}
