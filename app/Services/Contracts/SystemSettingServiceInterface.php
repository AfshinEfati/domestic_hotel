<?php

namespace App\Services\Contracts;

use App\Models\SystemSetting;

interface SystemSettingServiceInterface extends BaseServiceInterface
{
    public function store(mixed $payload): SystemSetting;

    public function update(int|string $id, mixed $payload): bool;

    public function getValue(string $key): mixed;
}
