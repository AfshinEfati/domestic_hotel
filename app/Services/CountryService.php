<?php

namespace App\Services;

use App\DTOs\CountryDTO;
use App\Models\Country;
use App\Repositories\Contracts\CountryRepositoryInterface;
use App\Services\Contracts\CountryServiceInterface;

class CountryService extends BaseService implements CountryServiceInterface
{
    public function __construct(CountryRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }

    /**
     * @return iterable<Country>
     */
    public function index(): iterable
    {
        /** @var iterable<Country> */
        return parent::index();
    }

    public function show(int|string $id): ?Country
    {
        /** @var Country|null */
        return parent::show($id);
    }

    /**
     * @param CountryDTO|array $payload
     * @return Country
     */
    public function store(mixed $payload): Country
    {
        if ($payload instanceof CountryDTO) {
            $payload = $payload->toArray();
        }

        /** @var Country */
        return parent::store($payload);
    }

    /**
     * @param int|string $id
     * @param CountryDTO|array $payload
     */
    public function update(int|string $id, mixed $payload): bool
    {
        if ($payload instanceof CountryDTO) {
            $payload = $payload->toArray();
        }

        return parent::update($id, $payload);
    }

    public function destroy(int|string $id): bool
    {
        return parent::destroy($id);
    }
}
