<?php

namespace App\Repositories\Eloquent;

use App\Models\ProviderRequest;
use App\Repositories\Contracts\ProviderRequestRepositoryInterface;

/**
 * @extends BaseRepository<ProviderRequest>
 */
class ProviderRequestRepository extends BaseRepository implements ProviderRequestRepositoryInterface
{
    public function __construct(ProviderRequest $model)
    {
        parent::__construct($model);
    }

    public function store(array $data): ProviderRequest
    {
        /** @var ProviderRequest */
        return parent::store($data);
    }
}
