<?php

namespace App\Repositories\Eloquent;

use App\Repositories\Contracts\AccommodationTypeRepositoryInterface;
use App\Repositories\Eloquent\BaseRepository;
use App\Models\AccommodationType;

class AccommodationTypeRepository extends BaseRepository implements AccommodationTypeRepositoryInterface
{
    public function __construct(AccommodationType $model)
    {
        parent::__construct($model);
    }

    /**
     * @return iterable<AccommodationType>
     */
    public function getAll(): iterable
    {
        /** @var iterable<AccommodationType> */
        return parent::getAll();
    }

    public function find(int|string $id): ?AccommodationType
    {
        /** @var AccommodationType|null */
        return parent::find($id);
    }

    public function store(array $data): AccommodationType
    {
        /** @var AccommodationType */
        return parent::store($data);
    }
}
