<?php

namespace App\Repositories\Contracts;

use App\Models\AccommodationType;

interface AccommodationTypeRepositoryInterface extends BaseRepositoryInterface
{
    /** @return iterable<AccommodationType> */
    public function getAll(): iterable;

    public function find(int|string $id): ?AccommodationType;

    public function store(array $data): AccommodationType;

    public function update(int|string $id, array $data): bool;

    public function delete(int|string $id): bool;

    /** Reuse the single seeded unknown row; never create a type per provider code. */
    public function getOrCreateUnknownType(): AccommodationType;
}
