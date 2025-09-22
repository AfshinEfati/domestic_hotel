<?php

namespace App\Services\Contracts;

use App\DTOs\ProviderDTO;
use App\Models\Provider;

interface ProviderServiceInterface extends BaseServiceInterface
{
    /**
     * @return iterable<Provider>
     */
    public function index(): iterable;

    public function show(int|string $id): ?Provider;

    /**
     * @param ProviderDTO|array $payload
     * @return Provider
     */
    public function store(mixed $payload): Provider;

    /**
     * @param int|string $id
     * @param ProviderDTO|array $payload
     */
    public function update(int|string $id, mixed $payload): bool;

    public function destroy(int|string $id): bool;
}
