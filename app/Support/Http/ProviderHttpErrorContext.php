<?php

namespace App\Support\Http;

use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Str;
use Psr\Http\Message\RequestInterface;

class ProviderHttpErrorContext
{
    public static function extract(RequestException $exception): array
    {
        try {
            $response = null;
            $request = null;

            // Get response using reflection
            if (property_exists($exception, 'response')) {
                $reflectionClass = new \ReflectionClass($exception);
                $responseProperty = $reflectionClass->getProperty('response');
                $responseProperty->setAccessible(true);
                $response = $responseProperty->getValue($exception);

                if ($response && method_exists($response, 'transferStats')) {
                    $stats = $response->transferStats();
                    if ($stats && method_exists($stats, 'getRequest')) {
                        $request = $stats->getRequest();
                    }
                }
            }

            return self::filterContext([
                'request' => self::formatRequestContext($request),
                'response' => self::formatResponseContext($response),
                'error_message' => $exception->getMessage(),
                'error_code' => $exception->getCode(),
            ]);
        } catch (\Throwable $e) {
            return [
                'error_extracting_context' => sprintf(
                    'Failed to extract HTTP context: %s at %s:%d',
                    $e->getMessage(),
                    $e->getFile(),
                    $e->getLine()
                ),
                'original_error' => [
                    'message' => $exception->getMessage(),
                    'code' => $exception->getCode(),
                    'file' => $exception->getFile(),
                    'line' => $exception->getLine(),
                ],
            ];
        }
    }

    private static function formatRequestContext(?RequestInterface $request): ?array
    {
        if (!$request) {
            return null;
        }

        $context = [
            'method' => $request->getMethod(),
            'url' => (string) $request->getUri(),
            'headers' => self::normalizeHeaders($request->getHeaders()),
        ];

        $body = (string) $request->getBody();
        if ('' !== $body) {
            $context['body'] = self::truncateString($body);
        }

        return self::filterContext($context);
    }

    private static function formatResponseContext(?Response $response): ?array
    {
        if (!$response) {
            return null;
        }

        $context = [
            'status' => method_exists($response, 'status') ? $response->status() : null,
            'headers' => method_exists($response, 'headers') ? self::normalizeHeaders($response->headers()) : null,
        ];

        if (method_exists($response, 'body')) {
            $body = $response->body();
            if ('' !== $body) {
                $context['body'] = self::truncateString($body);
            }
        }

        return self::filterContext($context);
    }

    private static function normalizeHeaders(?array $headers): ?array
    {
        if (!$headers) {
            return null;
        }

        foreach ($headers as $key => $value) {
            if (is_array($value) && 1 === count($value)) {
                $headers[$key] = $value[0];
            }
        }

        return $headers;
    }

    private static function filterContext(array $context): array
    {
        return array_filter($context, function ($value) {
            if (null === $value) {
                return false;
            }

            if (is_array($value)) {
                return !empty($value);
            }

            if (is_string($value)) {
                return '' !== $value;
            }

            return true;
        });
    }

    private static function truncateString(string $value, int $limit = 2000): string
    {
        return Str::limit($value, $limit, '...');
    }
}
