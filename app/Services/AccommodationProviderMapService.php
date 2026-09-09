<?php

namespace App\Services;

use App\DTOs\AccommodationProviderMapDTO;
use App\Models\AccommodationProviderMap;
use App\Repositories\Contracts\AccommodationProviderMapRepositoryInterface;
use App\Services\Contracts\AccommodationProviderMapServiceInterface;

class AccommodationProviderMapService extends BaseService implements AccommodationProviderMapServiceInterface
{
    public function __construct(AccommodationProviderMapRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }

    /**
     * @return iterable<AccommodationProviderMap>
     */
    public function index(): iterable
    {
        /** @var iterable<AccommodationProviderMap> */
        return parent::index();
    }

    public function show(int|string $id): ?AccommodationProviderMap
    {
        /** @var AccommodationProviderMap|null */
        return parent::show($id);
    }

    /**
     * @param AccommodationProviderMapDTO|array $payload
     * @return AccommodationProviderMap
     */
    public function store(mixed $payload): AccommodationProviderMap
    {
        if ($payload instanceof AccommodationProviderMapDTO) {
            $payload = $payload->toArray();
        }

        /** @var AccommodationProviderMap */
        return parent::store($payload);
    }

    /**
     * @param int|string $id
     * @param AccommodationProviderMapDTO|array $payload
     * @return bool
     */
    public function update(int|string $id, mixed $payload): bool
    {
        if ($payload instanceof AccommodationProviderMapDTO) {
            $payload = $payload->toArray();
        }

        return parent::update($id, $payload);
    }

    public function destroy(int|string $id): bool
    {
        return parent::destroy($id);
    }

    public function countMappedPropertiesByProvider(int $providerId): int
    {
        return $this->repository->countMappedPropertiesByProvider($providerId);
    }

    public function chunkMappedPropertiesByProvider(int $providerId, int $chunkSize, callable $callback): void
    {
        $this->repository->chunkMappedPropertiesByProvider($providerId, $chunkSize, $callback);
    }

    public function chunkByProvider(int $providerId, callable $callback): void
    {
        $this->repository->chunkByProvider($providerId, $callback);
    }

    public function chunkActive(callable $callback): void
    {
        $this->repository->chunkActive($callback);
    }

    protected function relations(): array
    {
        return [
            'accommodation.city',
            'accommodation.type',
            'accommodation.facilities',
            'provider',
        ];
    }
}
