<?php

namespace App\Repositories\Eloquent;

use App\Models\Provider;
use App\Repositories\Contracts\ProviderRepositoryInterface;

class ProviderRepository extends BaseRepository implements ProviderRepositoryInterface
{
    public function __construct(Provider $model)
    {
        parent::__construct($model);
    }

    /** @return iterable<Provider> */
    public function getAll(): iterable
    {
        /** @var iterable<Provider> */
        return parent::getAll();
    }

    public function find(int|string $id): ?Provider
    {
        /** @var Provider|null */
        return parent::find($id);
    }

    public function findByCode(string $code): ?Provider
    {
        return $this->model->newQuery()->where('code', $code)->first();
    }

    public function store(array $data): Provider
    {
        /** @var Provider */
        return parent::store($data);
    }
}
