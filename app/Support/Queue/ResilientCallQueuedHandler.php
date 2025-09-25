<?php

namespace App\Support\Queue;

use Exception;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Queue\CallQueuedHandler;
use Illuminate\Support\Str;

use function base_path;
use function class_exists;
use function get_object_vars;

class ResilientCallQueuedHandler extends CallQueuedHandler
{
    /**
     * Dispatch the given job / command through its specified middleware.
     */
    protected function dispatchThroughMiddleware(Job $job, $command)
    {
        $command = $this->resolveCommand($job, $command);

        if ($command instanceof \__PHP_Incomplete_Class) {
            throw new Exception('Job is incomplete class: '.json_encode($command));
        }

        $lockReleased = false;

        return (new Pipeline($this->container))->send($command)
            ->through(array_merge(method_exists($command, 'middleware') ? $command->middleware() : [], $command->middleware ?? []))
            ->finally(function ($command) use (&$lockReleased) {
                if (! $lockReleased && $command instanceof ShouldBeUniqueUntilProcessing && ! $command->job->isReleased()) {
                    $this->ensureUniqueJobLockIsReleased($command);
                }
            })
            ->then(function ($command) use ($job, &$lockReleased) {
                if ($command instanceof ShouldBeUniqueUntilProcessing) {
                    $this->ensureUniqueJobLockIsReleased($command);

                    $lockReleased = true;
                }

                return $this->dispatcher->dispatchNow(
                    $command, $this->resolveHandler($job, $command)
                );
            });
    }

    /**
     * Attempt to resolve an incomplete command instance.
     */
    protected function resolveCommand(Job $job, $command)
    {
        if (! $command instanceof \__PHP_Incomplete_Class) {
            return $command;
        }

        $className = $this->extractIncompleteClassName($command);
        if ($className && ! class_exists($className)) {
            $this->requireAppClassIfPresent($className);
        }

        $payload = $job->payload();
        $data = $payload['data'] ?? null;

        if (! is_array($data)) {
            return $command;
        }

        $rehydrated = $this->getCommand($data);

        return $rehydrated instanceof \__PHP_Incomplete_Class ? $command : $rehydrated;
    }

    /**
     * Extract the class name from an incomplete command instance.
     */
    private function extractIncompleteClassName(\__PHP_Incomplete_Class $command): ?string
    {
        $vars = get_object_vars($command);

        $class = $vars['__PHP_Incomplete_Class_Name'] ?? null;

        return is_string($class) ? $class : null;
    }

    /**
     * Include the PHP file for an application class if it exists.
     */
    private function requireAppClassIfPresent(string $className): void
    {
        if (! Str::startsWith($className, 'App\\')) {
            return;
        }

        $relative = substr($className, 4);
        $relativePath = 'app/'.str_replace('\\', '/', $relative).'.php';
        $fullPath = base_path($relativePath);

        if (is_file($fullPath)) {
            require_once $fullPath;
        }
    }
}
