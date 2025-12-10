<?php

namespace App\Services;

use App\DTOs\RatePlanProviderMapDTO;
use App\Models\RatePlanProviderMap;
use App\Repositories\Contracts\RatePlanProviderMapRepositoryInterface;
use App\Services\Contracts\RatePlanProviderMapServiceInterface;

class RatePlanProviderMapService extends BaseService implements RatePlanProviderMapServiceInterface
{
    public function __construct(RatePlanProviderMapRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }

    /**
     * @return iterable<RatePlanProviderMap>
     */
    public function index(): iterable
    {
        /** @var iterable<RatePlanProviderMap> */
        return parent::index();
    }

    public function show(int|string $id): ?RatePlanProviderMap
    {
        /** @var RatePlanProviderMap|null */
        return parent::show($id);
    }

    /**
     * @param RatePlanProviderMapDTO|array $payload
     * @return RatePlanProviderMap
     */
    public function store(mixed $payload): RatePlanProviderMap
    {
        if ($payload instanceof RatePlanProviderMapDTO) {
            $payload = $payload->toArray();
        }

        /** @var RatePlanProviderMap */
        return parent::store($payload);
    }

    /**
     * @param int|string $id
     * @param RatePlanProviderMapDTO|array $payload
     */
    public function update(int|string $id, mixed $payload): bool
    {
        if ($payload instanceof RatePlanProviderMapDTO) {
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
        return [
            'ratePlan.accommodation',
            'provider.cityMaps.city',
        ];
    }
}
