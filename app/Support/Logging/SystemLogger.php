<?php

namespace App\Support\Logging;

use App\Models\SystemLog;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use JsonSerializable;
use Psr\Log\LogLevel;
use Stringable;
use Throwable;

class SystemLogger
{
    public function info(string $method, string $message, array $context = []): void
    {
        $this->write(LogLevel::INFO, $method, $message, $context);
    }

    public function warning(string $method, string $message, array $context = []): void
    {
        $this->write(LogLevel::WARNING, $method, $message, $context);
    }

    public function error(string $method, string $message, array $context = []): void
    {
        $this->write(LogLevel::ERROR, $method, $message, $context);
    }

    private function write(string $level, string $method, string $message, array $context): void
    {
        $normalizedContext = $this->normalizeContext($context);
        $providerId = $this->extractProviderId($normalizedContext);

        SystemLog::query()->create([
            'level' => $level,
            'method' => $method,
            'message' => $message,
            'provider_id' => $providerId,
            'context' => $normalizedContext ?: null,
        ]);
    }

    private function extractProviderId(array &$context): ?int
    {
        if (!array_key_exists('provider_id', $context)) {
            return null;
        }

        $providerId = $context['provider_id'];
        unset($context['provider_id']);

        if (is_int($providerId)) {
            return $providerId;
        }

        if (is_numeric($providerId)) {
            return (int)$providerId;
        }

        return null;
    }

    private function normalizeContext(array $context): array
    {
        $normalized = [];

        foreach ($context as $key => $value) {
            $normalized[$key] = $this->normalizeValue($value);
        }

        return $normalized;
    }

    private function normalizeValue(mixed $value): mixed
    {
        if ($value instanceof Throwable) {
            return [
                'exception_class' => $value::class,
                'message' => $value->getMessage(),
                'code' => $value->getCode(),
                'file' => $value->getFile(),
                'line' => $value->getLine(),
                'trace' => collect($value->getTrace())->map(function (array $frame) {
                    return Arr::only($frame, ['file', 'line', 'function', 'class']);
                })->take(50)->values()->all(),
            ];
        }

        if ($value instanceof Arrayable) {
            return $value->toArray();
        }

        if ($value instanceof Jsonable) {
            return json_decode($value->toJson(), true);
        }

        if ($value instanceof JsonSerializable) {
            return $value->jsonSerialize();
        }

        if ($value instanceof Stringable) {
            return (string)$value;
        }

        if (is_array($value)) {
            return $this->normalizeContext($value);
        }

        if (is_object($value)) {
            return method_exists($value, '__toString') ? (string)$value : $value::class;
        }

        if (is_string($value)) {
            return Str::limit($value, 5000, '...');
        }

        return $value;
    }
}
