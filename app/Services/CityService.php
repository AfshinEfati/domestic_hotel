<?php

namespace App\Services;

use App\DTOs\CityDTO;
use App\Models\City;
use App\Repositories\Contracts\CityRepositoryInterface;
use App\Services\Contracts\CityServiceInterface;

class CityService extends BaseService implements CityServiceInterface
{
    public function __construct(CityRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }

    /**
     * @return iterable<City>
     */
    public function index(): iterable
    {
        /** @var iterable<City> */
        return parent::index();
    }

    public function show(int|string $id): ?City
    {
        /** @var City|null */
        return parent::show($id);
    }

    /**
     * @param CityDTO|array $payload
     * @return City
     */
    public function store(mixed $payload): City
    {
        if ($payload instanceof CityDTO) {
            $payload = $payload->toArray();
        }

        /** @var City */
        return parent::store($payload);
    }

    /**
     * @param int|string $id
     * @param CityDTO|array $payload
     */
    public function update(int|string $id, mixed $payload): bool
    {
        if ($payload instanceof CityDTO) {
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
        return ['country', 'state'];
    }
}
