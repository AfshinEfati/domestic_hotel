<?php

namespace App\Actions\HotelChildPolicy;

use App\Actions\BaseAction;
use App\Services\HotelChildPolicyService;
use App\Models\HotelChildPolicy;
use App\DTOs\HotelChildPolicyDTO;
use Psr\Log\LoggerInterface;

class UpdateHotelChildPolicyAction extends BaseAction
{
    public function __construct(
        private readonly HotelChildPolicyService $service,
        ?LoggerInterface $logger = null,
    ) {
        parent::__construct($logger);
    }

    protected function handle(mixed ...$arguments): ?HotelChildPolicy
    {
        $id = $arguments[0] ?? null;
        $payload = $arguments[1] ?? null;

        /** @var HotelChildPolicyDTO|array $payload */
        $updated = $this->service->update($id, $payload);

        if (!$updated) {
            return null;
        }

        return $this->service->show($id);
    }
}
