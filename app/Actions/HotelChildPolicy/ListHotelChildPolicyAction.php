<?php

namespace App\Actions\HotelChildPolicy;

use App\Actions\BaseAction;
use App\Services\HotelChildPolicyService;
use Psr\Log\LoggerInterface;

class ListHotelChildPolicyAction extends BaseAction
{
    public function __construct(
        private readonly HotelChildPolicyService $service,
        ?LoggerInterface $logger = null,
    ) {
        parent::__construct($logger);
    }

    protected function handle(mixed ...$arguments): iterable
    {
        return $this->service->index();
    }
}
