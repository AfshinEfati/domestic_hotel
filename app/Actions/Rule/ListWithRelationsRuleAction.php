<?php

namespace App\Actions\Rule;

use App\Actions\BaseAction;
use App\Services\RuleService;
use Psr\Log\LoggerInterface;
use ReflectionMethod;

class ListWithRelationsRuleAction extends BaseAction
{
    public function __construct(
        private readonly RuleService $service,
        ?LoggerInterface $logger = null,
    ) {
        parent::__construct($logger);
    }

    protected function handle(mixed ...$arguments): iterable
    {
        $input = $arguments[0] ?? [];

        // Ensure input is a numeric array
        $input = array_values($input);

        $method = new ReflectionMethod($this->service, 'getByDynamic');
        $params = $method->getParameters();

        $final = [];

        foreach ($params as $index => $param) {
            if (array_key_exists($index, $input)) {
                $final[] = $input[$index];
            } else {
                $final[] = $param->getDefaultValue();
            }
        }

        return $this->service->getByDynamic(...$final);
    }
}
