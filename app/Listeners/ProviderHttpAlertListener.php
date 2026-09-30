<?php

namespace App\Listeners;

use App\Jobs\Hotel\V2\RefreshGrsPropertyPricesJob;
use App\Services\Alerts\TelegramAlertService;
use Illuminate\Http\Client\Events\ConnectionFailed;
use Illuminate\Http\Client\Events\ResponseReceived;
use Illuminate\Http\Client\Request;

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

        $status = $event->response->status();
        if ($status < 400) {
            return;
        }

        if (
            $status === 404
            && $provider === 'grs'
            && $this->isScheduledAvailabilityOperation($event->request)
        ) {
            // RefreshGrsPropertyPricesJob reports this with hotel/date context,
            // disables the stale provider mapping and advances the schedule.
            return;
        }

        // Rate limiting is handled by provider-specific throttling/cooldown logic.
        // It is operational flow, not an alert-worthy incident.
        if ($status === 429) {
            return;
        }

        $this->alerts->providerFailure(
            $provider,
            $this->operation($event->request),
            'http',
            $status,
            null,
            $this->responseReason($event->response->body()),
        );
    }

    public function connectionFailed(ConnectionFailed $event): void
    {
        $provider = $this->provider($event->request);
        if ($provider === null) {
            return;
        }

        $this->alerts->providerFailure(
            $provider,
            $this->operation($event->request),
            'connection'
        );
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

    private function isScheduledAvailabilityOperation(Request $request): bool
    {
        $path = (string) (parse_url($request->url(), PHP_URL_PATH) ?: '');
        if (!str_ends_with($path, '/v1/available-rooms')) {
            return false;
        }

        $tag = $request->attributes()['domestic_provider'] ?? [];
        $tag = is_array($tag) ? $tag : [];
        $log = is_array($tag['log'] ?? null) ? $tag['log'] : [];

        return ($log['handler_class'] ?? null) === RefreshGrsPropertyPricesJob::class;
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
