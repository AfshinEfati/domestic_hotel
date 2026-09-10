<?php

namespace App\Repositories\Eloquent;

use App\Models\SystemSetting;
use App\Repositories\Contracts\SystemSettingRepositoryInterface;

class SystemSettingRepository extends BaseRepository implements SystemSettingRepositoryInterface
{
    public function __construct(SystemSetting $model)
    {
        parent::__construct($model);
    }

    public function find(int|string $id): ?SystemSetting
    {
        /** @var SystemSetting|null */
        return parent::find($id);
    }

    public function store(array $data): SystemSetting
    {
        /** @var SystemSetting */
        return parent::store($data);
    }

    public function findActiveByKey(string $key): ?SystemSetting
    {
        return $this->model
            ->newQuery()
            ->where('key', $key)
            ->where('is_active', true)
            ->first();
    }
}
