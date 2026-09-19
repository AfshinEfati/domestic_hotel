<?php

namespace App\Repositories\Eloquent;

use App\Models\RoomTypeProviderMap;
use App\Repositories\Contracts\RoomTypeProviderMapRepositoryInterface;
use Illuminate\Support\Collection;

class RoomTypeProviderMapRepository extends BaseRepository implements RoomTypeProviderMapRepositoryInterface
{
    public function __construct(RoomTypeProviderMap $model)
    {
        parent::__construct($model);
    }

    /** @return iterable<RoomTypeProviderMap> */
    public function getAll(): iterable
    {
        /** @var iterable<RoomTypeProviderMap> */
        return parent::getAll();
    }

    public function find(int|string $id): ?RoomTypeProviderMap
    {
        /** @var RoomTypeProviderMap|null */
        return parent::find($id);
    }

    public function mappedForProviderIds(int $providerId, array $providerRoomIds): Collection
    {
        if ($providerRoomIds === []) {
            return collect();
        }

        return $this->model->newQuery()
            ->where('provider_id', $providerId)
            ->whereIn('provider_room_type_id', $providerRoomIds)
            ->get()
            ->keyBy('provider_room_type_id');
    }

    public function store(array $data): RoomTypeProviderMap
    {
        /** @var RoomTypeProviderMap */
        return parent::store($data);
    }
}
