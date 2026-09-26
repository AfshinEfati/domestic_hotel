<?php

namespace App\Listeners;

use App\Services\Alerts\TelegramAlertService;
use Illuminate\Http\Client\Events\ConnectionFailed;
use Illuminate\Http\Client\Events\ResponseReceived;
use Illuminate\Http\Client\Request;
use Throwable;

/**
 * Observe tagged supplier calls even when an adapter catches their errors.
 * Telegram transport is never tagged, preventing recursive notifications.
 */
final class ProviderHttpAlertListener
{
    public function __construct(private readonly TelegramAlertService $alerts) {}

    public function response(ResponseReceived $event): void
    {
        $provider = $this->provider($event->request);
        if ($provider === null) {
            return;
        }

        try {
            $status = $event->response->status();
            $operation = $this->operation($event->request);

            if ($status >= 400) {
                $this->alerts->providerFailure(
                    $provider,
                    $operation,
                    'http',
                    $status,
                    null,
                    $this->responseReason($event->response->body()),
                );
                return;
            }

            // Do not decode large provider catalog responses only for monitoring.
            $body = $event->response->body();
            if ($body === '' || strlen($body) > 32768) {
                return;
            }

            $data = $event->response->json();
            if (!is_array($data)) {
                $this->alerts->providerFailure($provider, $operation, 'invalid_response', $status);
                return;
            }

            $failure = ($data['ok'] ?? null) === false
                || ($data['success'] ?? null) === false
                || ($data['is_success'] ?? null) === false
                || ($data['status'] ?? null) === 'error';

            // GRS can return an application-level error with HTTP 200.
            if ($provider === 'grs' && isset($data['code']) && is_numeric($data['code'])) {
                $failure = $failure || (int) $data['code'] !== 200;
            }

            if ($provider === 'parto'
                && str_ends_with(parse_url($event->request->url(), PHP_URL_PATH) ?: '', '/Authenticate/CreateSession')
                && empty($data['SessionId'])) {
                $failure = true;
            }

            if (!$failure) {
                return;
            }

            $code = $data['error_code'] ?? $data['code'] ?? null;
            $code = is_string($code) || is_int($code) ? (string) $code : null;
            $this->alerts->providerFailure(
                $provider,
                $operation,
                'business',
                $status,
                $code,
                $this->responseReason($event->response->body()),
            );
        } catch (Throwable $exception) {
            // Monitoring must not change the result of the original provider call.
            error_log('Provider HTTP alert observer unavailable: ' . $exception::class);
        }
    }

    public function connectionFailed(ConnectionFailed $event): void
    {
        $provider = $this->provider($event->request);
        if ($provider === null) {
            return;
        }

        try {
            $this->alerts->providerFailure($provider, $this->operation($event->request), 'connection');
        } catch (Throwable $exception) {
            error_log('Provider connection alert observer unavailable: ' . $exception::class);
        }
    }

    private function responseReason(string $body): ?string
    {
        if ($body === '' || strlen($body) > 8192) {
            return null;
        }

        $data = json_decode($body, true);
        if (!is_array($data)) {
            return null;
        }

        foreach ([
            $data['message'] ?? null,
            $data['error_message'] ?? null,
            $data['detail'] ?? null,
            is_array($data['error'] ?? null) ? ($data['error']['message'] ?? null) : ($data['error'] ?? null),
        ] as $candidate) {
            if (!is_scalar($candidate)) {
                continue;
            }

            $message = trim((string) $candidate);
            if ($message === '') {
                continue;
            }

            return function_exists('mb_substr')
                ? mb_substr($message, 0, 300, 'UTF-8')
                : substr($message, 0, 300);
        }

        return null;
    }

    private function provider(Request $request): ?string
    {
        $tag = $request->attributes()['domestic_provider'] ?? null;
        if (!is_array($tag) || !is_string($tag['code'] ?? null)) {
            return null;
        }

        // Only allow an identifier, never URLs, headers, credentials or request bodies.
        $code = strtolower($tag['code']);
        return preg_match('/^[a-z0-9_-]{1,30}$/D', $code) ? $code : null;
    }

    private function operation(Request $request): string
    {
        $path = (string) (parse_url($request->url(), PHP_URL_PATH) ?: '/');
        $segments = explode('/', trim($path, '/'));
        $safe = [];

        foreach ($segments as $segment) {
            $safe[] = preg_match('/^[a-zA-Z][a-zA-Z0-9_-]{0,30}$/D', $segment)
                ? $segment
                : '{id}';
        }

        return strtoupper($request->method()) . ' /' . implode('/', $safe);
    }
}
