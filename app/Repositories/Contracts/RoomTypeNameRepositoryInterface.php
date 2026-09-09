<?php

namespace App\Repositories\Contracts;

use App\Models\RoomTypeName;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends BaseRepositoryInterface<RoomTypeName>
 */
interface RoomTypeNameRepositoryInterface extends BaseRepositoryInterface {
    public function firstOrCreate(array $attributes = [], array $values = []): RoomTypeName;
    public function getList(mixed $validated);
}
