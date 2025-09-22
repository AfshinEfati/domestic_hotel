<?php

namespace App\Services;

use App\DTOs\ProviderCityMapDTO;
use App\Models\ProviderCityMap;
use App\Repositories\Contracts\ProviderCityMapRepositoryInterface;
use App\Services\Contracts\ProviderCityMapServiceInterface;

class ProviderCityMapService extends BaseService implements ProviderCityMapServiceInterface
{
    public function __construct(ProviderCityMapRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }

    /**
     * @return iterable<ProviderCityMap>
     */
    public function index(): iterable
    {
        /** @var iterable<ProviderCityMap> */
        return parent::index();
    }

    public function show(int|string $id): ?ProviderCityMap
    {
        /** @var ProviderCityMap|null */
        return parent::show($id);
    }

    /**
     * @param ProviderCityMapDTO|array $payload
     * @return ProviderCityMap
     */
    public function store(mixed $payload): ProviderCityMap
    {
        if ($payload instanceof ProviderCityMapDTO) {
            $payload = $payload->toArray();
        }

        /** @var ProviderCityMap */
        return parent::store($payload);
    }

    /**
     * @param int|string $id
     * @param ProviderCityMapDTO|array $payload
     */
    public function update(int|string $id, mixed $payload): bool
    {
        if ($payload instanceof ProviderCityMapDTO) {
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
        return ['city', 'provider'];
    }
}
