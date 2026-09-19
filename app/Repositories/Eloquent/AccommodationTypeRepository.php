<?php

namespace App\Repositories\Eloquent;

use App\Models\AccommodationType;
use App\Repositories\Contracts\AccommodationTypeRepositoryInterface;

class AccommodationTypeRepository extends BaseRepository implements AccommodationTypeRepositoryInterface
{
    public function __construct(AccommodationType $model)
    {
        parent::__construct($model);
    }

    /** @return iterable<AccommodationType> */
    public function getAll(): iterable
    {
        return parent::getAll();
    }

    public function find(int|string $id): ?AccommodationType
    {
        return parent::find($id);
    }

    public function store(array $data): AccommodationType
    {
        return parent::store($data);
    }

    public function getOrCreateUnknownType(): AccommodationType
    {
        // The AccommodationTypeSeeder creates this row before workers are run.
        // Do not hardcode ID 17: production already contains extra type IDs.
        return $this->model->newQuery()->firstOrCreate(
            ['fa_name' => 'نامشخص'],
            ['en_name' => 'unknown']
        );
    }
}
