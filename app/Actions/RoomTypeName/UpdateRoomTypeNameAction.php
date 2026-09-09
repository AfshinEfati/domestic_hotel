<?php

namespace App\Actions\RoomTypeName;

use App\Actions\BaseAction;
use App\Services\RoomTypeNameService;
use App\Models\RoomTypeName;
use App\DTOs\RoomTypeNameDTO;
use Psr\Log\LoggerInterface;

class UpdateRoomTypeNameAction extends BaseAction
{
    public function __construct(
        private readonly RoomTypeNameService $service,
        ?LoggerInterface $logger = null,
    ) {
        parent::__construct($logger);
    }

    protected function handle(mixed ...$arguments): ?RoomTypeName
    {
        $id = $arguments[0] ?? null;
        $payload = $arguments[1] ?? null;

        /** @var RoomTypeNameDTO|array $payload */
        $updated = $this->service->update($id, $payload);

        if (!$updated) {
            return null;
        }

        return $this->service->show($id);
    }
}
