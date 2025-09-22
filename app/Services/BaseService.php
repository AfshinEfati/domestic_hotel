<?php

namespace App\Services;

use App\Repositories\Contracts\BaseRepositoryInterface;
use App\Services\Contracts\BaseServiceInterface;
use BadMethodCallException;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;

abstract class BaseService implements BaseServiceInterface
{
    /**
     * @param BaseRepositoryInterface $repository Concrete repository for the service.
     */
    public function __construct(protected BaseRepositoryInterface $repository) {}

    public function repository(): BaseRepositoryInterface
    {
        return $this->repository;
    }

    public function index(): mixed
    {
        $result = $this->callRepository('getAll');

        return $this->loadRelations($result);
    }

    public function show(int|string $id): mixed
    {
        $result = $this->callRepository('find', [$id]);

        return $this->loadRelations($result);
    }

    public function store(mixed $payload): mixed
    {
        $payload = $this->normalisePayload($payload);
        $result = $this->callRepository('store', [$payload]);

        return $this->loadRelations($result);
    }

    public function update(int|string $id, mixed $payload): bool
    {
        $payload = $this->normalisePayload($payload);

        return (bool) $this->callRepository('update', [$id, $payload]);
    }

    public function destroy(int|string $id): bool
    {
        return (bool) $this->callRepository('delete', [$id]);
    }

    public function __call(string $method, array $parameters): mixed
    {
        if (method_exists($this->repository, $method)) {
            return $this->repository->{$method}(...$parameters);
        }

        throw new BadMethodCallException(sprintf('Method %s::%s does not exist.', static::class, $method));
    }

    public function loadRelations(mixed $resource): mixed
    {
        $relations = $this->relations();

        if (empty($relations) || $resource === null) {
            return $resource;
        }

        if ($resource instanceof Model) {
            $resource->loadMissing($relations);

            return $resource;
        }

        if ($resource instanceof EloquentCollection) {
            $resource->load($relations);

            return $resource;
        }

        return $resource;
    }

    protected function relations(): array
    {
        return [];
    }

    protected function callRepository(string $method, array $arguments = []): mixed
    {
        if (!method_exists($this->repository, $method)) {
            throw new BadMethodCallException(sprintf('Repository method %s::%s not found.', get_class($this->repository), $method));
        }

        return $this->repository->{$method}(...$arguments);
    }

    protected function normalisePayload(mixed $payload): array
    {
        if (is_array($payload)) {
            return $payload;
        }

        if (is_object($payload) && method_exists($payload, 'toArray')) {
            return $payload->toArray();
        }

        return (array) $payload;
    }
}
