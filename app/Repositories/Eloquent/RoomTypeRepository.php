<?php

namespace App\Repositories\Eloquent;

use App\Models\RoomType;
use App\Repositories\Contracts\RoomTypeRepositoryInterface;

class RoomTypeRepository extends BaseRepository implements RoomTypeRepositoryInterface
{
    public function __construct(RoomType $model)
    {
        parent::__construct($model);
    }

    /**
     * @return iterable<RoomType>
     */
    public function getAll(): iterable
    {
        /** @var iterable<RoomType> */
        return parent::getAll();
    }

    public function find(int|string $id): ?RoomType
    {
        /** @var RoomType|null */
        return parent::find($id);
    }

    public function store(array $data): RoomType
    {
        /** @var RoomType */
        return parent::store($data);
    }
}
