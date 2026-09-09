<?php

namespace App\Repositories\Eloquent;

use App\Models\Facility;
use App\Repositories\Contracts\FacilityRepositoryInterface;

class FacilityRepository extends BaseRepository implements FacilityRepositoryInterface
{
    public function __construct(Facility $model)
    {
        parent::__construct($model);
    }

    /**
     * @return iterable<Facility>
     */
    public function getAll(): iterable
    {
        /** @var iterable<Facility> */
        return parent::getAll();
    }

    public function find(int|string $id): ?Facility
    {
        /** @var Facility|null */
        return parent::find($id);
    }

    public function store(array $data): Facility
    {
        /** @var Facility */
        return parent::store($data);
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
            ->with(['group'])
            ->orderBy('id')
            ->get();
    }
}
