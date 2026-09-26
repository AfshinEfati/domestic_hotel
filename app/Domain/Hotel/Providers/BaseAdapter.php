<?php

namespace App\Domain\Hotel\Providers;

use App\Models\Provider;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

/**
 * Base adapter class for all hotel providers.
 * Handles provider config, base url, headers, and HTTP client.
 * Each provider must implement its own authenticate() method.
 */
abstract class BaseAdapter
{
    protected Provider $provider;
    protected string $baseUrl;
    protected array $headers = [];

    /**
     * Explicit business context for provider HTTP logging.
     *
     * @var array{
     *     reservation_id:?int,
     *     handler_class:?string,
     *     handler_method:?string,
     *     attempt:int,
     *     force:bool
     * }|null
     */
    protected ?array $requestLogContext = null;

    public function __construct(Provider $provider)
    {
        $this->provider = $provider;
        $conf = $provider->config ?? [];
        $this->baseUrl = rtrim((string)($conf['base_url'] ?? ''), '/');
    }

    /** Each adapter implements its own authentication logic */
    abstract public function authenticate(): void;

    public function withRequestLogContext(
        ?int $reservationId = null,
        ?string $handlerClass = null,
        ?string $handlerMethod = null,
        int $attempt = 1,
        bool $force = false,
    ): static {
        $this->requestLogContext = [
            'reservation_id' => $reservationId,
            'handler_class' => $handlerClass,
            'handler_method' => $handlerMethod,
            'attempt' => max(1, $attempt),
            'force' => $force,
        ];

        return $this;
    }

    protected function client(): PendingRequest
    {
        $log = $this->requestLogMetadata();

        return Http::withHeaders($this->headers)
            ->withAttributes([
                'domestic_provider' => [
                    'id' => (int) $this->provider->id,
                    'code' => (string) $this->provider->code,
                    'log' => $log,
                ],
            ])
            ->baseUrl($this->baseUrl)
            ->timeout(30);
    }

    protected function setHeader(string $key, string $value): void
    {
        $this->headers[$key] = $value;
    }

    protected function normalizeError(\Throwable $e): array
    {
        return [
            'ok'    => false,
            'error' => [
                'type'    => 'provider_error',
                'message' => $e->getMessage(),
                'code'    => $e->getCode(),
            ],
        ];
    }

    public function fetchRoomTypes(string $providerPropertyId): ?\Illuminate\Support\Collection
    {
        return null;
    }

    /**
     * Rate/capacity traffic is skipped by default. Reservation validation can
     * explicitly force logging through withRequestLogContext().
     *
     * @return array{
     *     enabled:bool,
     *     reservation_id:?int,
     *     handler_class:?string,
     *     handler_method:?string,
     *     attempt:int,
     *     started_at:string,
     *     started_microtime:float
     * }
     */
    private function requestLogMetadata(): array
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 20);
        $inAvailabilityFlow = false;
        $detectedClass = null;
        $detectedMethod = null;

        foreach ($trace as $frame) {
            $method = $frame['function'] ?? null;
            $class = $frame['class'] ?? null;

            if ($method === 'fetchAvailability') {
                $inAvailabilityFlow = true;
            }

            if (
                $detectedMethod === null
                && is_string($class)
                && is_a($class, self::class, true)
                && is_string($method)
                && !in_array($method, ['client', 'requestLogMetadata', 'authenticate'], true)
            ) {
                $detectedClass = $class;
                $detectedMethod = $method;
            }
        }

        $context = $this->requestLogContext ?? [];
        $force = ($context['force'] ?? false) === true;

        return [
            'enabled' => $force || !$inAvailabilityFlow,
            'reservation_id' => isset($context['reservation_id'])
                ? (int) $context['reservation_id']
                : null,
            'handler_class' => $context['handler_class'] ?? $detectedClass ?? static::class,
            'handler_method' => $context['handler_method'] ?? $detectedMethod,
            'attempt' => max(1, (int) ($context['attempt'] ?? 1)),
            'started_at' => now()->toISOString(),
            'started_microtime' => microtime(true),
        ];
    }
}
