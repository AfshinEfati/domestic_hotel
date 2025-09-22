<?php

namespace App\Services;

use App\DTOs\RoomTypeProviderMapDTO;
use App\Models\RoomTypeProviderMap;
use App\Repositories\Contracts\RoomTypeProviderMapRepositoryInterface;
use App\Services\Contracts\RoomTypeProviderMapServiceInterface;

class RoomTypeProviderMapService extends BaseService implements RoomTypeProviderMapServiceInterface
{
    public function __construct(RoomTypeProviderMapRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }

    /**
     * @return iterable<RoomTypeProviderMap>
     */
    public function index(): iterable
    {
        /** @var iterable<RoomTypeProviderMap> */
        return parent::index();
    }

    public function show(int|string $id): ?RoomTypeProviderMap
    {
        /** @var RoomTypeProviderMap|null */
        return parent::show($id);
    }

    /**
     * @param RoomTypeProviderMapDTO|array $payload
     * @return RoomTypeProviderMap
     */
    public function store(mixed $payload): RoomTypeProviderMap
    {
        if ($payload instanceof RoomTypeProviderMapDTO) {
            $payload = $payload->toArray();
        }

        /** @var RoomTypeProviderMap */
        return parent::store($payload);
    }

    /**
     * @param int|string $id
     * @param RoomTypeProviderMapDTO|array $payload
     */
    public function update(int|string $id, mixed $payload): bool
    {
        if ($payload instanceof RoomTypeProviderMapDTO) {
            $payload = $payload->toArray();
        }

        return parent::update($id, $payload);
    }

    public function destroy(int|string $id): bool
    {
        return parent::destroy($id);
    }

    protected function relations(): array
    {
        return [
            'roomType.accommodation.city',
            'roomType.accommodation.type',
            'roomType.accommodation.facilities',
            'provider.cityMaps.city',
        ];
    }
}
