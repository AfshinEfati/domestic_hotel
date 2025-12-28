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

    public function getList(array $filters): iterable
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
