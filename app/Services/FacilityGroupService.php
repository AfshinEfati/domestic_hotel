<?php

namespace App\Services;

use App\DTOs\FacilityGroupDTO;
use App\Models\FacilityGroup;
use App\Repositories\Contracts\FacilityGroupRepositoryInterface;
use App\Services\Contracts\FacilityGroupServiceInterface;

class FacilityGroupService extends BaseService implements FacilityGroupServiceInterface
{
    public function __construct(FacilityGroupRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }

    /**
     * @return iterable<FacilityGroup>
     */
    public function index(): iterable
    {
        /** @var iterable<FacilityGroup> */
        return parent::index();
    }

    public function show(int|string $id): ?FacilityGroup
    {
        /** @var FacilityGroup|null */
        return parent::show($id);
    }

    /**
     * @param FacilityGroupDTO|array $payload
     * @return FacilityGroup
     */
    public function store(mixed $payload): FacilityGroup
    {
        if ($payload instanceof FacilityGroupDTO) {
            $payload = $payload->toArray();
        }

        /** @var FacilityGroup */
        return parent::store($payload);
    }

    /**
     * @param int|string $id
     * @param FacilityGroupDTO|array $payload
     */
    public function update(int|string $id, mixed $payload): bool
    {
        if ($payload instanceof FacilityGroupDTO) {
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
        return ['facilities.group'];
    }
}
