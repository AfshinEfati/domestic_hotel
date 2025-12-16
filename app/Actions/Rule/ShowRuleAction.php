<?php

namespace App\Actions\Rule;

use App\Actions\BaseAction;
use App\Services\RuleService;
use App\Models\Rule;
use Psr\Log\LoggerInterface;

class ShowRuleAction extends BaseAction
{
    public function __construct(
        private readonly RuleService $service,
        ?LoggerInterface $logger = null,
    ) {
        parent::__construct($logger);
    }

    protected function handle(mixed ...$arguments): ?Rule
    {
        $modelOrId = $arguments[0] ?? null;

        if ($modelOrId instanceof Rule) {
            $modelOrId = $modelOrId->getKey();
        }

        return $this->service->show($modelOrId);
    }
}
