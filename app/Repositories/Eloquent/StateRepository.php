<?php

namespace App\Repositories\Eloquent;

use App\Models\State;
use App\Repositories\Contracts\StateRepositoryInterface;

class StateRepository extends BaseRepository implements StateRepositoryInterface
{
    public function __construct(State $model)
    {
        parent::__construct($model);
    }

    /**
     * @return iterable<State>
     */
    public function getAll(): iterable
    {
        /** @var iterable<State> */
        return parent::getAll();
    }

    public function find(int|string $id): ?State
    {
        /** @var State|null */
        return parent::find($id);
    }

    public function store(array $data): State
    {
        /** @var State */
        return parent::store($data);
    }
}
