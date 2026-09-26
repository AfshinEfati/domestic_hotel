<?php

namespace App\Services\Contracts;

use App\Models\ProviderRequest;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\Response;

interface ProviderRequestServiceInterface
{
    public function recordResponse(Request $request, Response $response): ?ProviderRequest;

    public function recordConnectionFailure(Request $request): ?ProviderRequest;
}
