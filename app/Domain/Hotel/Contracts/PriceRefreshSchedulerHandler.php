<?php

namespace App\Domain\Hotel\Contracts;

use App\Models\Provider;

/** Each provider owns its settings and dispatch policy; the scheduler owns no API rules. */
interface PriceRefreshSchedulerHandler
{
    public function dispatchIfEnabled(Provider $provider): bool;
}
