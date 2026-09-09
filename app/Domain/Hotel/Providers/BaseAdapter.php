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

    public function __construct(Provider $provider)
    {
        $this->provider = $provider;
        $conf = $provider->config ?? [];
        $this->baseUrl = rtrim((string)($conf['base_url'] ?? ''), '/');
    }

    /** Each adapter implements its own authentication logic */
    abstract public function authenticate(): void;

    protected function client(): PendingRequest
    {
        return Http::withHeaders($this->headers)
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
}
