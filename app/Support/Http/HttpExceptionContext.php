<?php

namespace App\Support\Http;

use Illuminate\Http\Client\RequestException;

class HttpExceptionContext
{
    public static function extract(RequestException $exception): array
    {
        $context = [
            'exception_details' => [
                'class' => get_class($exception),
                'message' => $exception->getMessage(),
                'code' => $exception->getCode(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString(),
            ],
        ];

        try {
            $reflectionClass = new \ReflectionClass($exception);
            if ($reflectionClass->hasProperty('response')) {
                $responseProperty = $reflectionClass->getProperty('response');
                $responseProperty->setAccessible(true);
                $response = $responseProperty->getValue($exception);

                if ($response) {
                    $context['response'] = [
                        'status' => method_exists($response, 'status') ? $response->status() : null,
                        'headers' => method_exists($response, 'headers') ? $response->headers() : [],
                    ];

                    if (method_exists($response, 'body')) {
                        $responseBody = $response->body();
                        $context['response']['body'] = $responseBody;

                        try {
                            $context['response']['json'] = json_decode($responseBody, true);
                        } catch (\Throwable $e) {
                            $context['response']['json_parse_error'] = $e->getMessage();
                        }
                    }

                    if (method_exists($response, 'effectiveUri')) {
                        $context['response']['url'] = (string) $response->effectiveUri();
                    }

                    if (method_exists($response, 'effectiveMethod')) {
                        $context['response']['method'] = $response->effectiveMethod();
                    }
                }
            }
        } catch (\Throwable $e) {
            $context['error_extracting_response'] = sprintf(
                'Failed to extract response details: %s at %s:%d',
                $e->getMessage(),
                $e->getFile(),
                $e->getLine()
            );
        }

        return $context;
    }
}
