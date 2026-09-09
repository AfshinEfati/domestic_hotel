<?php

namespace App\Actions\RoomTypeName;

use App\Actions\BaseAction;
use App\Services\RoomTypeNameService;
use App\DTOs\RoomTypeNameDTO;
use Psr\Log\LoggerInterface;

class CreateRoomTypeNameAction extends BaseAction
{
    public function __construct(
        private readonly RoomTypeNameService $service,
        ?LoggerInterface $logger = null,
    ) {
        parent::__construct($logger);
    }

    protected function handle(mixed ...$arguments): RoomTypeName
    {
        $payload = $arguments[0] ?? null;

        /** @var RoomTypeNameDTO|array $payload */
        return $this->service->store($payload);
    }
}
