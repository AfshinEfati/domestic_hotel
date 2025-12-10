<?php

namespace App\Services;

use App\DTOs\RatePlanDTO;
use App\Models\RatePlan;
use App\Repositories\Contracts\RatePlanRepositoryInterface;
use App\Services\Contracts\RatePlanServiceInterface;

class RatePlanService extends BaseService implements RatePlanServiceInterface
{
    public function __construct(RatePlanRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }

    /**
     * @return iterable<RatePlan>
     */
    public function index(): iterable
    {
        /** @var iterable<RatePlan> */
        return parent::index();
    }

    public function show(int|string $id): ?RatePlan
    {
        /** @var RatePlan|null */
        return parent::show($id);
    }

    /**
     * @param RatePlanDTO|array $payload
     * @return RatePlan
     */
    public function store(mixed $payload): RatePlan
    {
        if ($payload instanceof RatePlanDTO) {
            $payload = $payload->toArray();
        }

        /** @var RatePlan */
        return parent::store($payload);
    }

    /**
     * @param int|string $id
     * @param RatePlanDTO|array $payload
     */
    public function update(int|string $id, mixed $payload): bool
    {
        if ($payload instanceof RatePlanDTO) {
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
        return ['accommodation'];
    }
}
