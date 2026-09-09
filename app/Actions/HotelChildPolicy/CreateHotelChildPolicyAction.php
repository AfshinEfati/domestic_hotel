<?php

namespace App\Actions\HotelChildPolicy;

use App\Actions\BaseAction;
use App\Services\HotelChildPolicyService;
use App\DTOs\HotelChildPolicyDTO;
use Psr\Log\LoggerInterface;

class CreateHotelChildPolicyAction extends BaseAction
{
    public function __construct(
        private readonly HotelChildPolicyService $service,
        ?LoggerInterface $logger = null,
    ) {
        parent::__construct($logger);
    }

    protected function handle(mixed ...$arguments): HotelChildPolicy
    {
        $payload = $arguments[0] ?? null;

        /** @var HotelChildPolicyDTO|array $payload */
        return $this->service->store($payload);
    }
}
