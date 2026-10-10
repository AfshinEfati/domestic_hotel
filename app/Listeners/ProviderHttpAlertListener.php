<?php

namespace App\Listeners;

use App\Jobs\Hotel\V2\RefreshGrsPropertyPricesJob;
use App\Modules\HotelProviders\V2\SnappTrip\Support\SnappTripCalendarRangeLimit;
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
            $provider === 'snap'
            && $this->isSnappTripCalendarOperation($event->request)
            && SnappTripCalendarRangeLimit::fromResponse($event->response) !== null
        ) {
            // The SnappTrip refresh flow learns this per-hotel window and retries the
            // same horizon with smaller chunks. It is an expected self-healing signal.
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

        $context = $this->alertContext($event->request);

        if ($status === 429) {
            // The scheduled GRS price worker handles its own 429 with hotel context,
            // shared cooldown and delayed retry. Avoid a duplicate generic alert.
            if ($provider === 'grs' && $this->isScheduledPriceRefreshOperation($event->request)) {
                return;
            }

            $retryAfter = $event->response->header('Retry-After');

            $this->alerts->providerRateLimited(
                $provider,
                $this->operation($event->request),
                is_numeric($retryAfter) ? max(1, (int) $retryAfter) : null,
                $this->responseReason($event->response->body()),
                $context,
            );

            return;
        }

        $this->alerts->providerFailure(
            $provider,
            $this->operation($event->request),
            'http',
            $status,
            null,
            $this->responseReason($event->response->body()),
            $context,
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
            'connection',
            context: $this->alertContext($event->request),
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

    /** @return array<string,string|int> */
    private function alertContext(Request $request): array
    {
        $tag = $request->attributes()['domestic_provider'] ?? null;
        if (!is_array($tag)) {
            return [];
        }

        $fields = [];

        if (isset($tag['id']) && is_numeric($tag['id']) && (int) $tag['id'] > 0) {
            $fields['شناسه تأمین‌کننده'] = (int) $tag['id'];
        }

        $context = is_array($tag['context'] ?? null) ? $tag['context'] : [];
        if (isset($context['accommodation_id']) && is_numeric($context['accommodation_id']) && (int) $context['accommodation_id'] > 0) {
            $fields['شناسه هتل'] = (int) $context['accommodation_id'];
        }

        $providerPropertyId = trim((string) ($context['provider_property_id'] ?? ''));
        if ($providerPropertyId !== '' && preg_match('/^[A-Za-z0-9_.:-]{1,64}$/D', $providerPropertyId)) {
            $fields['شناسه هتل تأمین‌کننده'] = $providerPropertyId;
        }

        $query = parse_url($request->url(), PHP_URL_QUERY);
        if (is_string($query) && $query !== '') {
            parse_str($query, $params);
            $from = isset($params['from']) && is_string($params['from']) ? trim($params['from']) : null;
            $to = isset($params['to']) && is_string($params['to']) ? trim($params['to']) : null;

            if (
                $from !== null
                && $to !== null
                && preg_match('/^\d{4}-\d{2}-\d{2}$/D', $from)
                && preg_match('/^\d{4}-\d{2}-\d{2}$/D', $to)
            ) {
                $fields['بازه درخواست'] = $from.' تا '.$to;
            }
        }

        return $fields;
    }

    private function isSnappTripCalendarOperation(Request $request): bool
    {
        $path = (string) (parse_url($request->url(), PHP_URL_PATH) ?: '');

        return str_ends_with($path, '/calendar')
            && str_contains($path, '/availability/hotels/');
    }

    private function isScheduledAvailabilityOperation(Request $request): bool
    {
        $path = (string) (parse_url($request->url(), PHP_URL_PATH) ?: '');
        if (!str_ends_with($path, '/v1/available-rooms')) {
            return false;
        }

        return $this->isScheduledPriceRefreshOperation($request);
    }

    private function isScheduledPriceRefreshOperation(Request $request): bool
    {
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
