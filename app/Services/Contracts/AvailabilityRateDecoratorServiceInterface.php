<?php

namespace App\Services\Contracts;

interface AvailabilityRateDecoratorServiceInterface
{
    public function decorate(iterable $accommodations): iterable;
}
