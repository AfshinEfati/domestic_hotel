<?php

namespace App\Listeners;

use App\Services\Contracts\ProviderRequestServiceInterface;
use Illuminate\Http\Client\Events\ConnectionFailed;
use Illuminate\Http\Client\Events\ResponseReceived;
use Throwable;

/**
 * Persists tagged provider traffic without changing the original HTTP result.
 */
final class ProviderHttpRequestListener
{
    public function __construct(private readonly ProviderRequestServiceInterface $requests) {}

    public function response(ResponseReceived $event): void
    {
        try {
            $this->requests->recordResponse($event->request, $event->response);
        } catch (Throwable) {
            // Request logging must never affect provider traffic.
        }
    }

    public function connectionFailed(ConnectionFailed $event): void
    {
        try {
            $this->requests->recordConnectionFailure($event->request);
        } catch (Throwable) {
            // Request logging must never affect provider traffic.
        }
    }
}
