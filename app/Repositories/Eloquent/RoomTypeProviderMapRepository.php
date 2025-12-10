<?php

namespace App\Repositories\Eloquent;

use App\Models\RoomTypeProviderMap;
use App\Repositories\Contracts\RoomTypeProviderMapRepositoryInterface;

class RoomTypeProviderMapRepository extends BaseRepository implements RoomTypeProviderMapRepositoryInterface
{
    public function __construct(RoomTypeProviderMap $model)
    {
        parent::__construct($model);
    }

    /**
     * @return iterable<RoomTypeProviderMap>
     */
    public function getAll(): iterable
    {
        /** @var iterable<RoomTypeProviderMap> */
        return parent::getAll();
    }

    public function find(int|string $id): ?RoomTypeProviderMap
    {
        /** @var RoomTypeProviderMap|null */
        return parent::find($id);
    }

    public function store(array $data): RoomTypeProviderMap
    {
        /** @var RoomTypeProviderMap */
        return parent::store($data);
    }
}
