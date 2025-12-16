<?php

namespace App\Services;

use App\Services\Contracts\RuleServiceInterface;
use App\Services\BaseService;
use App\Models\Rule;
use App\Repositories\Contracts\RuleRepositoryInterface;
use App\DTOs\RuleDTO;

/**
 * @extends BaseService<Rule>
 */
class RuleService extends BaseService implements RuleServiceInterface
{
    public function __construct(RuleRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }
}
