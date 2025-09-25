<?php

namespace App\Support\Queue;

use Exception;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Queue\CallQueuedHandler;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use ReflectionClass;
use Throwable;

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

        if (! $className) {
            return $command;
        }

        if (! class_exists($className)) {
            $this->requireAppClassIfPresent($className);
        }

        $rehydrated = $this->rehydrateFromPayload($job);
        if ($rehydrated && ! $rehydrated instanceof \__PHP_Incomplete_Class) {
            return $this->setJobInstanceIfNecessary($job, $rehydrated);
        }

        $rehydrated = $this->rehydrateFromProperties($command, $className);
        if ($rehydrated) {
            return $this->setJobInstanceIfNecessary($job, $rehydrated);
        }

        return $command;
    }

    /**
     * Attempt to rehydrate the command from the job payload.
     */
    private function rehydrateFromPayload(Job $job): mixed
    {
        try {
            $payload = $job->payload();
        } catch (Throwable) {
            return null;
        }

        $data = Arr::get($payload, 'data');

        if (! is_array($data) || ! array_key_exists('command', $data)) {
            return null;
        }

        try {
            return $this->getCommand($data);
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Attempt to rebuild the command using the properties of the incomplete instance.
     */
    private function rehydrateFromProperties(\__PHP_Incomplete_Class $command, string $className): mixed
    {
        if (! class_exists($className)) {
            return null;
        }

        $properties = get_object_vars($command);
        unset($properties['__PHP_Incomplete_Class_Name']);

        try {
            $reflection = new ReflectionClass($className);
            $instance = $reflection->newInstanceWithoutConstructor();

            foreach ($properties as $name => $value) {
                if ($reflection->hasProperty($name)) {
                    $property = $reflection->getProperty($name);
                    $property->setAccessible(true);
                    $property->setValue($instance, $value);

                    continue;
                }

                $instance->{$name} = $value;
            }

            return $instance;
        } catch (Throwable) {
            return null;
        }
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
