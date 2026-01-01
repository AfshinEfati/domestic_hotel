<?php

namespace App\Services\Contracts;

use App\DTOs\RoomTypeProviderMapDTO;
use App\Models\RoomTypeProviderMap;

interface RoomTypeProviderMapServiceInterface extends BaseServiceInterface
{
    /**
     * @return iterable<RoomTypeProviderMap>
     */
    public function index(): iterable;

    public function show(int|string $id): ?RoomTypeProviderMap;

    /**
     * @param RoomTypeProviderMapDTO|array $payload
     * @return RoomTypeProviderMap
     */
    public function store(mixed $payload): RoomTypeProviderMap;

    /**
     * @param int|string $id
     * @param RoomTypeProviderMapDTO|array $payload
     */
    public function update(int|string $id, mixed $payload): bool;

    public function destroy(int|string $id): bool;
    public function findByProviderAndRemoteId(int $providerId, string $remoteId): ?RoomTypeProviderMap;}
