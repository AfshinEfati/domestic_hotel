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
    public function getList(array $filters): iterable
    {
        $query = $this->model->query();
        if (isset($filters['from'], $filters['to'])) {
            $from = (int)$filters['from'];
            $to = (int)$filters['to'];
            $query->skip($from)->take($to);
        }
        return $query
            ->orderBy('id')
            ->get();
    }
}
