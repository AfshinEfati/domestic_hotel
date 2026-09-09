<?php

namespace App\Repositories\Eloquent;

use App\Models\FacilityGroup;
use App\Repositories\Contracts\FacilityGroupRepositoryInterface;
use Illuminate\Database\Eloquent\Model;

class FacilityGroupRepository extends BaseRepository implements FacilityGroupRepositoryInterface
{
    public function __construct(FacilityGroup $model)
    {
        parent::__construct($model);
    }

    /**
     * @return iterable<FacilityGroup>
     */
    public function getAll(): iterable
    {
        /** @var iterable<FacilityGroup> */
        return parent::getAll();
    }

    public function find(int|string $id): ?FacilityGroup
    {
        /** @var FacilityGroup|null */
        return parent::find($id);
    }

    public function store(array $data): FacilityGroup
    {
        /** @var FacilityGroup */
        return parent::store($data);
    }

    public function firstOrCreate(array $attributes = [], array $values = []): Model
    {

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
            ->with(['facilities'])
            ->orderBy('id')
            ->get();
    }
}
