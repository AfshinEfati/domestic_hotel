<?php

namespace App\Services\Contracts;

use App\DTOs\RoomTypeDTO;
use App\Models\RoomType;

interface RoomTypeServiceInterface extends BaseServiceInterface
{
    /**
     * @return iterable<RoomType>
     */
    public function index(): iterable;

    public function show(int|string $id): ?RoomType;

    /**
     * @param RoomTypeDTO|array $payload
     * @return RoomType
     */
    public function store(mixed $payload): RoomType;

    /**
     * @param int|string $id
     * @param RoomTypeDTO|array $payload
     */
    public function update(int|string $id, mixed $payload): bool;

    public function destroy(int|string $id): bool;
    public function findByAccommodationAndName(int $accommodationId, string $name): ?RoomType;}
