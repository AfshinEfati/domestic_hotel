<?php

namespace App\Services;

use App\Models\ProviderRequest;
use App\Repositories\Contracts\ProviderRepositoryInterface;
use App\Repositories\Contracts\ProviderRequestRepositoryInterface;
use App\Services\Contracts\ProviderRequestServiceInterface;
use App\Support\Provider\ProviderRequestStatus;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\Response;

readonly class ProviderRequestService implements ProviderRequestServiceInterface
{
    private const RETENTION_DAYS = 15;

    public function __construct(
        private ProviderRequestRepositoryInterface $requests,
        private ProviderRepositoryInterface $providers,
    ) {}

    public function recordResponse(Request $request, Response $response): ?ProviderRequest
    {
        $context = $this->context($request);
        if (!$context['enabled']) {
            return null;
        }

        $providerId = $this->providerId($context);
        if ($providerId === null) {
            return null;
        }

        $httpStatus = $response->status();
        $finishedAt = CarbonImmutable::now();

        return $this->requests->store([
            'provider_id' => $providerId,
            'reservation_id' => $context['reservation_id'],
            'handler_class' => $context['handler_class'],
            'handler_method' => $context['handler_method'],
            'http_method' => strtoupper($request->method()),
            'url' => $request->url(),
            'request_body' => $this->nullableBody($request->body()),
            'response_body' => $this->nullableBody($response->body()),
            'http_status' => $httpStatus,
            'status' => $httpStatus >= 400
                ? ProviderRequestStatus::HTTP_FAILED
                : ProviderRequestStatus::SUCCESS,
            'attempt' => $context['attempt'],
            'exception_class' => null,
            'error_message' => $httpStatus >= 400
                ? "Provider returned HTTP {$httpStatus}."
                : null,
            'started_at' => $context['started_at'],
            'finished_at' => $finishedAt,
            'duration_ms' => $this->durationMs($context),
            'expires_at' => $finishedAt->addDays(self::RETENTION_DAYS),
        ]);
    }

    public function recordConnectionFailure(Request $request): ?ProviderRequest
    {
        $context = $this->context($request);
        if (!$context['enabled']) {
            return null;
        }

        $providerId = $this->providerId($context);
        if ($providerId === null) {
            return null;
        }

        $finishedAt = CarbonImmutable::now();

        return $this->requests->store([
            'provider_id' => $providerId,
            'reservation_id' => $context['reservation_id'],
            'handler_class' => $context['handler_class'],
            'handler_method' => $context['handler_method'],
            'http_method' => strtoupper($request->method()),
            'url' => $request->url(),
            'request_body' => $this->nullableBody($request->body()),
            'response_body' => null,
            'http_status' => null,
            'status' => ProviderRequestStatus::CONNECTION_FAILED,
            'attempt' => $context['attempt'],
            'exception_class' => ConnectionException::class,
            'error_message' => 'Provider connection failed before receiving a response.',
            'started_at' => $context['started_at'],
            'finished_at' => $finishedAt,
            'duration_ms' => $this->durationMs($context),
            'expires_at' => $finishedAt->addDays(self::RETENTION_DAYS),
        ]);
    }

    /**
     * @return array{
     *     enabled:bool,
     *     provider_id:?int,
     *     provider_code:?string,
     *     reservation_id:?int,
     *     handler_class:?string,
     *     handler_method:?string,
     *     attempt:int,
     *     started_at:CarbonImmutable,
     *     started_microtime:?float
     * }
     */
    private function context(Request $request): array
    {
        $tag = $request->attributes()['domestic_provider'] ?? [];
        $tag = is_array($tag) ? $tag : [];
        $log = is_array($tag['log'] ?? null) ? $tag['log'] : [];

        $startedAt = isset($log['started_at']) && is_string($log['started_at'])
            ? CarbonImmutable::parse($log['started_at'])
            : CarbonImmutable::now();

        return [
            'enabled' => ($log['enabled'] ?? true) === true,
            'provider_id' => isset($tag['id']) && is_numeric($tag['id']) ? (int) $tag['id'] : null,
            'provider_code' => is_string($tag['code'] ?? null) ? strtolower($tag['code']) : null,
            'reservation_id' => isset($log['reservation_id']) && is_numeric($log['reservation_id'])
                ? (int) $log['reservation_id']
                : null,
            'handler_class' => is_string($log['handler_class'] ?? null) ? $log['handler_class'] : null,
            'handler_method' => is_string($log['handler_method'] ?? null) ? $log['handler_method'] : null,
            'attempt' => max(1, (int) ($log['attempt'] ?? 1)),
            'started_at' => $startedAt,
            'started_microtime' => isset($log['started_microtime']) && is_numeric($log['started_microtime'])
                ? (float) $log['started_microtime']
                : null,
        ];
    }

    private function providerId(array $context): ?int
    {
        if ($context['provider_id'] !== null) {
            return $context['provider_id'];
        }

        if ($context['provider_code'] === null) {
            return null;
        }

        return $this->providers->findByCode($context['provider_code'])?->id;
    }

    private function durationMs(array $context): ?int
    {
        if ($context['started_microtime'] === null) {
            return null;
        }

        return max(0, (int) round((microtime(true) - $context['started_microtime']) * 1000));
    }

    private function nullableBody(string $body): ?string
    {
        return $body === '' ? null : $body;
    }
}
