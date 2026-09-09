<?php

namespace App\Actions\HotelChildPolicy;

use App\Actions\BaseAction;
use App\Services\HotelChildPolicyService;
use App\Models\HotelChildPolicy;
use Psr\Log\LoggerInterface;

class ShowHotelChildPolicyAction extends BaseAction
{
    public function __construct(
        private readonly HotelChildPolicyService $service,
        ?LoggerInterface $logger = null,
    ) {
        parent::__construct($logger);
    }

    protected function handle(mixed ...$arguments): ?HotelChildPolicy
    {
        $modelOrId = $arguments[0] ?? null;

        if ($modelOrId instanceof HotelChildPolicy) {
            $modelOrId = $modelOrId->getKey();
        }

        return $this->service->show($modelOrId);
    }
}
