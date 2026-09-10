<?php

namespace App\Repositories\Contracts;

use App\Models\HotelSetting;

interface HotelSettingRepositoryInterface extends BaseRepositoryInterface
{
    public function find(int|string $id): ?HotelSetting;

    public function store(array $data): HotelSetting;

    public function findActiveByKey(string $key): ?HotelSetting;
}
