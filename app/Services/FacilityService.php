<?php

namespace App\Services;

use App\DTOs\FacilityDTO;
use App\Models\Facility;
use App\Repositories\Contracts\FacilityRepositoryInterface;
use App\Services\Contracts\FacilityServiceInterface;

class FacilityService extends BaseService implements FacilityServiceInterface
{
    public function __construct(FacilityRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }

    /**
     * @return iterable<Facility>
     */
    public function index(): iterable
    {
        /** @var iterable<Facility> */
        return parent::index();
    }

    public function show(int|string $id): ?Facility
    {
        /** @var Facility|null */
        return parent::show($id);
    }

    /**
     * @param FacilityDTO|array $payload
     * @return Facility
     */
    public function store(mixed $payload): Facility
    {
        if ($payload instanceof FacilityDTO) {
            $payload = $payload->toArray();
        }

        /** @var Facility */
        return parent::store($payload);
    }

    /**
     * @param int|string $id
     * @param FacilityDTO|array $payload
     */
    public function update(int|string $id, mixed $payload): bool
    {
        if ($payload instanceof FacilityDTO) {
            $payload = $payload->toArray();
        }

        return parent::update($id, $payload);
    }

    public function destroy(int|string $id): bool
    {
        return parent::destroy($id);
    }

    public function getList(mixed $validated)
    {
        return $this->repository->getList($validated);
    }

    protected function relations(): array
    {
        return ['group'];
    }
}
