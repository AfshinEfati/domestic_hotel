<?php

namespace App\Modules\HotelProviders\V2\SnappTrip\Application\Contracts;

interface BalanceSynchronizer
{
    public function execute(): int;
}
