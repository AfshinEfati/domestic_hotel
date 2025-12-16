<?php

namespace App\Actions\Rule;

use App\Actions\BaseAction;
use App\Services\RuleService;
use App\Models\Rule;
use App\DTOs\RuleDTO;
use Psr\Log\LoggerInterface;

class UpdateRuleAction extends BaseAction
{
    public function __construct(
        private readonly RuleService $service,
        ?LoggerInterface $logger = null,
    ) {
        parent::__construct($logger);
    }

    protected function handle(mixed ...$arguments): ?Rule
    {
        $id = $arguments[0] ?? null;
        $payload = $arguments[1] ?? null;

        /** @var RuleDTO|array $payload */
        $updated = $this->service->update($id, $payload);

        if (!$updated) {
            return null;
        }

        return $this->service->show($id);
    }
}
