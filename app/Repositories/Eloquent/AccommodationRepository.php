<?php

namespace App\Repositories\Eloquent;

use App\Repositories\Contracts\AccommodationRepositoryInterface;
use App\Repositories\Eloquent\BaseRepository;
use App\Models\Accommodation;

class AccommodationRepository extends BaseRepository implements AccommodationRepositoryInterface
{
    public function __construct(Accommodation $model)
    {
        parent::__construct($model);
    }

    /**
     * @return iterable<Accommodation>
     */
    public function getAll(): iterable
    {
        /** @var iterable<Accommodation> */
        return parent::getAll();
    }

    public function find(int|string $id): ?Accommodation
    {
        /** @var Accommodation|null */
        return parent::find($id);
    }

    public function store(array $data): Accommodation
    {
        /** @var Accommodation */
        return parent::store($data);
    }
}
