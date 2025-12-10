<?php

namespace App\Services;

use App\DTOs\StateDTO;
use App\Models\State;
use App\Repositories\Contracts\StateRepositoryInterface;
use App\Services\Contracts\StateServiceInterface;

class StateService extends BaseService implements StateServiceInterface
{
    public function __construct(StateRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }

    /**
     * @return iterable<State>
     */
    public function index(): iterable
    {
        /** @var iterable<State> */
        return parent::index();
    }

    public function show(int|string $id): ?State
    {
        /** @var State|null */
        return parent::show($id);
    }

    /**
     * @param StateDTO|array $payload
     * @return State
     */
    public function store(mixed $payload): State
    {
        if ($payload instanceof StateDTO) {
            $payload = $payload->toArray();
        }

        /** @var State */
        return parent::store($payload);
    }

    /**
     * @param int|string $id
     * @param StateDTO|array $payload
     */
    public function update(int|string $id, mixed $payload): bool
    {
        if ($payload instanceof StateDTO) {
            $payload = $payload->toArray();
        }

        return parent::update($id, $payload);
    }

    public function destroy(int|string $id): bool
    {
        return parent::destroy($id);
    }

    protected function relations(): array
    {
        return ['country'];
    }
}
