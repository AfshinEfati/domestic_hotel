<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobErrorLog extends Model
{
    protected $fillable = [
        'job_id',
        'job_class',
        'queue',
        'attempts',
        'max_attempts',
        'job_payload',
        'error_class',
        'error_message',
        'error_code',
        'error_file',
        'error_line',
        'error_trace',
        'http_status_code',
        'http_response_body',
        'http_request_url',
        'http_request_method',
        'http_response_headers',
        'additional_context',
    ];

    protected $casts = [
        'job_payload' => 'array',
        'http_response_headers' => 'array',
        'additional_context' => 'array',
        'attempts' => 'integer',
        'max_attempts' => 'integer',
        'error_code' => 'integer',
        'error_line' => 'integer',
        'http_status_code' => 'integer',
    ];

    /**
     * Create a new error log entry from an exception and job context.
     */
    public static function createFromException(\Throwable $exception, array $jobContext = []): self
    {
        $data = [
            'error_class' => get_class($exception),
            'error_message' => $exception->getMessage(),
            'error_code' => $exception->getCode(),
            'error_file' => $exception->getFile(),
            'error_line' => $exception->getLine(),
            'error_trace' => $exception->getTraceAsString(),
        ];

        // Add HTTP context for RequestException
        if ($exception instanceof \Illuminate\Http\Client\RequestException) {
            try {
                // Get response information using reflection if needed
                $response = (new \ReflectionClass($exception))->getProperty('response');
                $response->setAccessible(true);
                $responseObj = $response->getValue($exception);

                if ($responseObj) {
                    $data['http_status_code'] = $responseObj->status();
                    $data['http_response_body'] = $responseObj->body();
                    $data['http_request_url'] = (string) $responseObj->effectiveUri();
                    $data['http_request_method'] = $responseObj->effectiveMethod();
                    $data['http_response_headers'] = $responseObj->headers();
                }
            } catch (\Throwable $e) {
                // If we can't get response details, at least store what we can
                $data['http_error_context'] = 'Failed to extract HTTP response details: '.$e->getMessage();
            }
        }

        return static::create(array_merge($data, $jobContext));
    }
}