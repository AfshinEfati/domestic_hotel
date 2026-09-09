<?php

namespace App\Services;

use App\Models\RoomTypeName;
use App\Repositories\Contracts\BaseRepositoryInterface;
use App\Repositories\Contracts\RoomTypeNameRepositoryInterface;
use App\Services\Contracts\RoomTypeNameServiceInterface;

class RoomTypeNameService extends BaseService implements RoomTypeNameServiceInterface
{
    protected BaseRepositoryInterface $repository;
    public function __construct(RoomTypeNameRepositoryInterface $repository)
    {
        parent::__construct($repository);
        $this->repository = $repository;

    }

    public function firstOrCreate(array $attributes, array $values = []): RoomTypeName
    {
        return $this->repository->firstOrCreate($attributes, $values);
    }

    public function getList(mixed $validated)
    {
        return $this->repository->getList($validated);
    }
}
