<?php

namespace App\Repositories\Eloquent;

use App\Models\HotelSetting;
use App\Repositories\Contracts\HotelSettingRepositoryInterface;

class HotelSettingRepository extends BaseRepository implements HotelSettingRepositoryInterface
{
    public function __construct(HotelSetting $model)
    {
        parent::__construct($model);
    }

    public function find(int|string $id): ?HotelSetting
    {
        /** @var HotelSetting|null */
        return parent::find($id);
    }

    public function store(array $data): HotelSetting
    {
        /** @var HotelSetting */
        return parent::store($data);
    }

    public function findActiveByKey(string $key): ?HotelSetting
    {
        return $this->model
            ->newQuery()
            ->where('key', $key)
            ->where('is_active', true)
            ->first();
    }
}
