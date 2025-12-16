<?php

namespace App\Actions\Rule;

use App\Actions\BaseAction;
use App\Services\RuleService;
use Psr\Log\LoggerInterface;

class DeleteRuleAction extends BaseAction
{
    public function __construct(
        private readonly RuleService $service,
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
