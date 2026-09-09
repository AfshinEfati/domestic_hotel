<?php

namespace App\Repositories\Eloquent;

use App\Models\RoomTypeName;
use App\Repositories\Contracts\RoomTypeNameRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use LaravelIdea\Helper\App\Models\_IH_RoomTypeName_C;

/**
 * @extends BaseRepository<RoomTypeName>
 */
class RoomTypeNameRepository extends BaseRepository implements RoomTypeNameRepositoryInterface
{
    public function __construct(RoomTypeName $model)
    {
        parent::__construct($model);
    }

    public function firstOrCreate(array $attributes = [], array $values = []): RoomTypeName
    {
        return $this->model->firstOrCreate($attributes, $values);
    }

    public function getList(mixed $validated): array|Collection|_IH_RoomTypeName_C
    {
        $query = $this->model->query();
        if (isset($filters['from'], $filters['to'])) {
            $from = (int)$filters['from'];
            $to = (int)$filters['to'];
            $query->skip($from)->take($to);
        }
        return $query
            ->orderBy('id')
            ->get();
    }
}
