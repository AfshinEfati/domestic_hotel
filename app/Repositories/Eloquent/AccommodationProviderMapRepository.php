<?php

namespace App\Repositories\Eloquent;

use App\Models\AccommodationProviderMap;
use App\Repositories\Contracts\AccommodationProviderMapRepositoryInterface;

class AccommodationProviderMapRepository extends BaseRepository implements AccommodationProviderMapRepositoryInterface
{
    public function __construct(AccommodationProviderMap $model)
    {
        parent::__construct($model);
    }

    /**
     * @return iterable<AccommodationProviderMap>
     */
    public function getAll(): iterable
    {
        /** @var iterable<AccommodationProviderMap> */
        return parent::getAll();
    }

    public function find(int|string $id): ?AccommodationProviderMap
    {
        /** @var AccommodationProviderMap|null */
        return parent::find($id);
    }

    public function store(array $data): AccommodationProviderMap
    {
        /** @var AccommodationProviderMap */
        return parent::store($data);
    }

    public function chunkByProvider(int $providerId, callable $callback): void
    {
        $this->model->where('provider_id', $providerId)->chunk(100, $callback);
    }
}
