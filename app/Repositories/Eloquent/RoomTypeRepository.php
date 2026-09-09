<?php

namespace App\Repositories\Eloquent;

use App\Models\RoomType;
use App\Repositories\Contracts\RoomTypeRepositoryInterface;
use Illuminate\Database\Eloquent\Model;

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

    public function firstOrCreate(array $attributes = [], array $values = []): Model
    {
        // TODO: Implement firstOrCreate() method.
    }

    public function updateOrCreate(array $attributes, array $values = []): Model
    {
        // TODO: Implement updateOrCreate() method.
    }

    public function getList(array $filters = []): iterable
    {
        $query = $this->model->query();
        if (isset($filters['from'], $filters['to'])) {
            $from = (int)$filters['from'];
            $to = (int)$filters['to'];
            $query->skip($from)->take($to);
        }
        return $query
            ->with(['roomTypeName'])
            ->orderBy('id')
            ->get();
    }
}
