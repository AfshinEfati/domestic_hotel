<?php

namespace App\Repositories\Eloquent;

use App\Repositories\Contracts\RuleRepositoryInterface;
use App\Repositories\Eloquent\BaseRepository;
use App\Models\Rule;

/**
 * @extends BaseRepository<Rule>
 */
class RuleRepository extends BaseRepository implements RuleRepositoryInterface
{
    public function __construct(Rule $model)
    {
        parent::__construct($model);
    }
}
