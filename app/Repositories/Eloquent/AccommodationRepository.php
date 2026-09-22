<?php

namespace App\Repositories\Eloquent;

use App\Repositories\Contracts\AccommodationRepositoryInterface;
use App\Repositories\Eloquent\BaseRepository;
use App\Models\Accommodation;
use App\Services\AvailabilityFilterService;
use Illuminate\Database\Eloquent\Model;

class AccommodationRepository extends BaseRepository implements AccommodationRepositoryInterface
{
    private AvailabilityFilterService $filterService;

    public function __construct(Accommodation $model, AvailabilityFilterService $filterService)
    {
        parent::__construct($model);
        $this->filterService = $filterService;
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
            $from = (int) $filters['from'];
            $to = (int) $filters['to'];
            $query->skip($from)->take($to);
        }

        $perPage = max((int) ($filters['per_page'] ?? 20), 1);
        $page = max((int) ($filters['page'] ?? 1), 1);

        return $query
            ->with(['city', 'type','facilities','facilities.group','rooms','rooms.roomTypeName','rules','childPolicy'])
            ->orderBy('id')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    public function getAvailability(array $data): iterable
    {
        return $this->filterService->filterAvailability($data);
    }

    public function firstOrCreate(array $attributes = [], array $values = []): Model
    {
        return $this->model->firstOrCreate($attributes, $values);
    }

    public function updateOrCreate(array $attributes, array $values = []): Model
    {
        return $this->model->updateOrCreate($attributes, $values);
    }
}
