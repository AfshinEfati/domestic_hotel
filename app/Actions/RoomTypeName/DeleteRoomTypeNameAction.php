<?php

namespace App\Actions\RoomTypeName;

use App\Actions\BaseAction;
use App\Services\RoomTypeNameService;
use Psr\Log\LoggerInterface;

class DeleteRoomTypeNameAction extends BaseAction
{
    public function __construct(
        private readonly RoomTypeNameService $service,
        ?LoggerInterface $logger = null,
    ) {
        parent::__construct($logger);
    }

    protected function handle(mixed ...$arguments): bool
    {
        $id = $arguments[0] ?? null;

        return $this->service->destroy($id);
    }
}
