<?php

namespace App\Services\Contracts;

use App\Models\RoomTypeName;

/**
 * @extends BaseServiceInterface<RoomTypeName>
 */
interface RoomTypeNameServiceInterface extends BaseServiceInterface
{
    public function firstOrCreate(array $attributes, array $values = []): RoomTypeName;
}
