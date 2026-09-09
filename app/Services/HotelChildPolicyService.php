<?php

namespace App\Services;

use App\Services\Contracts\HotelChildPolicyServiceInterface;
use App\Services\BaseService;
use App\Models\HotelChildPolicy;
use App\Repositories\Contracts\HotelChildPolicyRepositoryInterface;
use App\DTOs\HotelChildPolicyDTO;

/**
 * @extends BaseService<HotelChildPolicy>
 */
class HotelChildPolicyService extends BaseService implements HotelChildPolicyServiceInterface
{
    public function __construct(HotelChildPolicyRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }

    public function getList(mixed $validated)
    {
        return $this->repository->getList($validated);
    }
}
