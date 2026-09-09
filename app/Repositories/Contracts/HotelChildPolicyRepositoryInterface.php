<?php

namespace App\Repositories\Contracts;

use App\Models\HotelChildPolicy;
use App\Repositories\Contracts\BaseRepositoryInterface;

/**
 * @extends BaseRepositoryInterface<HotelChildPolicy>
 */
interface HotelChildPolicyRepositoryInterface extends BaseRepositoryInterface
{
    public function getList(array $filters): iterable;
}
