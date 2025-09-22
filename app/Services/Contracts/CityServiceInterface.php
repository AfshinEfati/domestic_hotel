<?php

namespace App\Services\Contracts;

use App\DTOs\CityDTO;
use App\Models\City;

interface CityServiceInterface extends BaseServiceInterface
{
    /**
     * @return iterable<City>
     */
    public function index(): iterable;

    public function show(int|string $id): ?City;

    /**
     * @param CityDTO|array $payload
     * @return City
     */
    public function store(mixed $payload): City;

    /**
     * @param int|string $id
     * @param CityDTO|array $payload
     */
    public function update(int|string $id, mixed $payload): bool;

    public function destroy(int|string $id): bool;
}
