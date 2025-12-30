<?php

namespace App\Repositories\Eloquent;

use App\Models\SystemLog;
use App\Repositories\Contracts\SystemLogRepositoryInterface;

/**
 * @extends BaseRepository<SystemLog>
 */
class SystemLogRepository extends BaseRepository implements SystemLogRepositoryInterface
{
    public function __construct(SystemLog $model)
    {
        parent::__construct($model);
    }
}
