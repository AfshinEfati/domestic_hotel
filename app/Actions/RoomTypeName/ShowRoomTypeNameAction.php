<?php

namespace App\Actions\RoomTypeName;

use App\Actions\BaseAction;
use App\Services\RoomTypeNameService;
use App\Models\RoomTypeName;
use Psr\Log\LoggerInterface;

class ShowRoomTypeNameAction extends BaseAction
{
    public function __construct(
        private readonly RoomTypeNameService $service,
        ?LoggerInterface $logger = null,
    ) {
        parent::__construct($logger);
    }

    protected function handle(mixed ...$arguments): ?RoomTypeName
    {
        $modelOrId = $arguments[0] ?? null;

        if ($modelOrId instanceof RoomTypeName) {
            $modelOrId = $modelOrId->getKey();
        }

        return $this->service->show($modelOrId);
    }
}
