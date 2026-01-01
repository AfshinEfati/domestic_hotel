<?php

namespace App\Services;

use App\DTOs\ProviderDTO;
use App\Models\Provider;
use App\Repositories\Contracts\ProviderRepositoryInterface;
use App\Services\Contracts\ProviderServiceInterface;

class ProviderService extends BaseService implements ProviderServiceInterface
{
    public function __construct(ProviderRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }

    /**
     * @return iterable<Provider>
     */
    public function index(): iterable
    {
        /** @var iterable<Provider> */
        return parent::index();
    }

    public function show(int|string $id): ?Provider
    {
        /** @var Provider|null */
        return parent::show($id);
    }

    /**
     * @param ProviderDTO|array $payload
     * @return Provider
     */
    public function store(mixed $payload): Provider
    {
        if ($payload instanceof ProviderDTO) {
            $payload = $payload->toArray();
        }

        /** @var Provider */
        return parent::store($payload);
    }

    /**
     * @param int|string $id
     * @param ProviderDTO|array $payload
     */
    public function update(int|string $id, mixed $payload): bool
    {
        if ($payload instanceof ProviderDTO) {
            $payload = $payload->toArray();
        }

        return parent::update($id, $payload);
    }

    public function destroy(int|string $id): bool
    {
        return parent::destroy($id);
    }

    public function getActiveProviders(): iterable
    {
        return $this->repository->getByDynamic(['is_active' => true]);
    }

    protected function relations(): array
    {
        return ['cityMaps.city'];
    }
}
