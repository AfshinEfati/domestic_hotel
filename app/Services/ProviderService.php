<?php

namespace App\Services;

use App\DTOs\ProviderDTO;
use App\Models\Provider;
use App\Repositories\Contracts\AccommodationRepositoryInterface;
use App\Repositories\Contracts\ProviderRepositoryInterface;
use App\Services\Contracts\ProviderServiceInterface;

class ProviderService extends BaseService implements ProviderServiceInterface
{
    public function __construct(
        ProviderRepositoryInterface $repository,
        private readonly AccommodationRepositoryInterface $accommodationRepository,
    ) {
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

        if (array_key_exists('config', $payload) && is_array($payload['config'])) {
            /** @var Provider|null $provider */
            $provider = $this->repository->find($id);

            if ($provider !== null) {
                $payload['config'] = array_replace_recursive(
                    $provider->config ?? [],
                    $payload['config']
                );
            }
        }

        return parent::update($id, $payload);
    }

    public function destroy(int|string $id): bool
    {
        return parent::destroy($id);
    }

    public function storeOfflineByAccommodationId(int $accommodationId): Provider
    {
        $accommodation = $this->accommodationRepository->find($accommodationId);

        if ($accommodation === null) {
            throw new \InvalidArgumentException('Accommodation not found.');
        }

        /** @var Provider $provider */
        $provider = $this->repository->updateOrCreate(
            ['code' => 'hotel-'.$accommodation->id],
            [
                'fa_name' => $accommodation->fa_name,
                'en_name' => $accommodation->en_name,
                'config' => null,
                'is_active' => true,
                'is_online' => false,
            ]
        );

        return $provider;
    }

    public function getActiveProviders(): iterable
    {
        return $this->repository->getByDynamic([
            'is_active' => true,
            'is_online' => true,
        ]);
    }

    protected function relations(): array
    {
        return ['cityMaps.city'];
    }
}
