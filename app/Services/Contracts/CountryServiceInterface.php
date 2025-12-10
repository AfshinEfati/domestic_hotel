<?php

namespace App\Services\Contracts;

use App\DTOs\CountryDTO;
use App\Models\Country;

interface CountryServiceInterface extends BaseServiceInterface
{
    /**
     * @return iterable<Country>
     */
    public function index(): iterable;

    public function show(int|string $id): ?Country;

    /**
     * @param CountryDTO|array $payload
     * @return Country
     */
    public function store(mixed $payload): Country;

    /**
     * @param int|string $id
     * @param CountryDTO|array $payload
     */
    public function update(int|string $id, mixed $payload): bool;

    public function destroy(int|string $id): bool;
}
