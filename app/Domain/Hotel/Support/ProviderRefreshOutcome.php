<?php

namespace App\Domain\Hotel\Support;

final class ProviderRefreshOutcome
{
    public const SUCCESS = 'success';
    public const PROVIDER_404 = 'provider_404';
    public const PROVIDER_DISABLED = 'provider_disabled';
    public const PROVIDER_HTTP_ERROR = 'provider_http_error';
    public const PROVIDER_EMPTY = 'provider_empty';
    public const PROVIDER_TIMEOUT = 'provider_timeout';
    public const PROVIDER_INVALID_RESPONSE = 'provider_invalid_response';
    public const MAP_DISABLED = 'map_disabled';
    public const MAP_UNAVAILABLE = 'map_unavailable';
    public const SCHEDULER_DISABLED = 'scheduler_disabled';
    public const CYCLE_SUPERSEDED = 'cycle_superseded';
    public const RATE_LIMITED = 'rate_limited';
    public const REQUEST_ERROR = 'request_error';
    public const INTERNAL_ERROR = 'internal_error';
    public const PERSISTENCE_ERROR = 'persistence_error';
    public const MAPPING_NOT_READY = 'mapping_not_ready';
}
