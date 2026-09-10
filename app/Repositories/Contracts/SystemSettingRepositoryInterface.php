<?php

namespace App\Repositories\Contracts;

use App\Models\SystemSetting;

interface SystemSettingRepositoryInterface extends BaseRepositoryInterface
{
    public function find(int|string $id): ?SystemSetting;

    public function store(array $data): SystemSetting;

    public function findActiveByKey(string $key): ?SystemSetting;
}
