<?php

namespace App\Services;

use App\Services\Contracts\AccommodationServiceInterface;
use App\Services\BaseService;
use App\Models\Accommodation;
use App\Repositories\Contracts\AccommodationRepositoryInterface;
use App\DTOs\AccommodationDTO;

class AccommodationService extends BaseService implements AccommodationServiceInterface
{
    public function __construct(AccommodationRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }

    /**
     * @return iterable<Accommodation>
     */
    public function index(): iterable
    {
        /** @var iterable<Accommodation> */
        return parent::index();
    }

    public function show(int|string $id): ?Accommodation
    {
        /** @var Accommodation|null */
        return parent::show($id);
    }

    /**
     * @param AccommodationDTO|array $payload
     * @return Accommodation
     */
    public function store(mixed $payload): Accommodation
    {
        if ($payload instanceof AccommodationDTO) {
            $payload = $payload->toArray();
        }

        /** @var Accommodation */
        return parent::store($payload);
    }

    /**
     * @param int|string $id
     * @param AccommodationDTO|array $payload
     * @return bool
     */
    public function update(int|string $id, mixed $payload): bool
    {
        if ($payload instanceof AccommodationDTO) {
            $payload = $payload->toArray();
        }

        return parent::update($id, $payload);
    }

    public function destroy(int|string $id): bool
    {
        return parent::destroy($id);
    }

    public function getAvailability(mixed $validated)
    {
        return $this->repository->getAvailability($validated);
    }

    protected function relations(): array
    {
        return [
            'city',
            'type',
            'facilities',
            'room-types',
            'room-types.roomTypeName',
            'facilities.group',
            'rules',
        ];
    }

    public function getList(array $data): iterable
    {
        return $this->repository->getList($data);
    }
}
