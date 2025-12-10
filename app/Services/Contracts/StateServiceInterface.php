<?php

namespace App\Services\Contracts;

use App\DTOs\StateDTO;
use App\Models\State;

interface StateServiceInterface extends BaseServiceInterface
{
    /**
     * @return iterable<State>
     */
    public function index(): iterable;

    public function show(int|string $id): ?State;

    /**
     * @param StateDTO|array $payload
     * @return State
     */
    public function store(mixed $payload): State;

    /**
     * @param int|string $id
     * @param StateDTO|array $payload
     */
    public function update(int|string $id, mixed $payload): bool;

    public function destroy(int|string $id): bool;
}
