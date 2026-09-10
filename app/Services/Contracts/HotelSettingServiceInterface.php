<?php

namespace App\Services\Contracts;

use App\Models\HotelSetting;

interface HotelSettingServiceInterface extends BaseServiceInterface
{
    public function store(mixed $payload): HotelSetting;

    public function update(int|string $id, mixed $payload): bool;

    public function getValue(string $key): mixed;
}
