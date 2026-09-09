<?php

namespace App\Services;

use App\DTOs\RoomTypeDTO;
use App\Models\RoomType;
use App\Repositories\Contracts\RoomTypeRepositoryInterface;
use App\Services\Contracts\RoomTypeServiceInterface;

class RoomTypeService extends BaseService implements RoomTypeServiceInterface
{
    public function __construct(RoomTypeRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }

    /**
     * @return iterable<RoomType>
     */
    public function index(): iterable
    {
        /** @var iterable<RoomType> */
        return parent::index();
    }

    public function show(int|string $id): ?RoomType
    {
        /** @var RoomType|null */
        return parent::show($id);
    }

    /**
     * @param RoomTypeDTO|array $payload
     * @return RoomType
     */
    public function store(mixed $payload): RoomType
    {
        if ($payload instanceof RoomTypeDTO) {
            $payload = $payload->toArray();
        }

        /** @var RoomType */
        return parent::store($payload);
    }

    /**
     * @param int|string $id
     * @param RoomTypeDTO|array $payload
     */
    public function update(int|string $id, mixed $payload): bool
    {
        if ($payload instanceof RoomTypeDTO) {
            $payload = $payload->toArray();
        }

        return parent::update($id, $payload);
    }

    public function destroy(int|string $id): bool
    {
        return parent::destroy($id);
    }

    public function findByAccommodationAndName(int $accommodationId, string $name): ?RoomType
    {
        return $this->repository->findDynamic([
            'accommodation_id' => $accommodationId,
            'fa_name' => $name
        ]);
    }

    public function getList(mixed $validated)
    {
        return $this->repository->getList($validated);
    }

    protected function relations(): array
    {
        return [
            'accommodation.city',
            'accommodation.type',
            'accommodation.facilities',
        ];
    }
}
