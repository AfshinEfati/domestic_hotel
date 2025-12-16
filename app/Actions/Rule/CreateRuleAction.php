<?php

namespace App\Actions\Rule;

use App\Actions\BaseAction;
use App\Services\RuleService;
use App\DTOs\RuleDTO;
use Psr\Log\LoggerInterface;

class CreateRuleAction extends BaseAction
{
    public function __construct(
        private readonly RuleService $service,
        ?LoggerInterface $logger = null,
    ) {
        parent::__construct($logger);
    }

    protected function handle(mixed ...$arguments): Rule
    {
        $payload = $arguments[0] ?? null;

        /** @var RuleDTO|array $payload */
        return $this->service->store($payload);
    }
}
