<?php

namespace App\Repositories\Contracts;

use App\Models\Rule;
use App\Repositories\Contracts\BaseRepositoryInterface;

/**
 * @extends BaseRepositoryInterface<Rule>
 */
interface RuleRepositoryInterface extends BaseRepositoryInterface
{
    public function getList(array $filters): iterable;

}
