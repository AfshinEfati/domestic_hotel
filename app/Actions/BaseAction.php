<?php

namespace App\Actions;

abstract class BaseAction
{
    public function __invoke(mixed ...$arguments): mixed
    {
        return $this->execute(...$arguments);
    }

    public function execute(mixed ...$arguments): mixed
    {
        return $this->handle(...$arguments);
    }

    abstract protected function handle(mixed ...$arguments): mixed;
}
