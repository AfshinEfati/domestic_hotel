<?php

namespace App\Repositories\Contracts;

use App\Models\ProviderRequest;

/**
 * @extends BaseRepositoryInterface<ProviderRequest>
 */
interface ProviderRequestRepositoryInterface extends BaseRepositoryInterface
{
    public function store(array $data): ProviderRequest;
}
