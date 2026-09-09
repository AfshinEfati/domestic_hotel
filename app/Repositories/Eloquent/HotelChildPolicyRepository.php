<?php

namespace App\Repositories\Eloquent;

use App\Repositories\Contracts\HotelChildPolicyRepositoryInterface;
use App\Repositories\Eloquent\BaseRepository;
use App\Models\HotelChildPolicy;

/**
 * @extends BaseRepository<HotelChildPolicy>
 */
class HotelChildPolicyRepository extends BaseRepository implements HotelChildPolicyRepositoryInterface
{
    public function __construct(HotelChildPolicy $model)
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
