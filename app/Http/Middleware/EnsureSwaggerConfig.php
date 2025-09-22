<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use L5Swagger\ConfigFactory;

/**
 * Ensure that Swagger requests have the expected configuration payload.
 */
class EnsureSwaggerConfig
{
    public function __construct(private readonly ConfigFactory $configFactory)
    {
    }

    /**
     * Attach the Swagger configuration to the current request when required.
     *
     * @param  Request  $request
     * @param  Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $route = $request->route();

        if (! $this->isSwaggerRoute($route)) {
            return $next($request);
        }

        $documentation = $this->resolveDocumentationName($route);
        $config = $this->configFactory->documentationConfig($documentation);

        $request->merge([
            'documentation' => $documentation,
            'config' => $config,
        ]);

        return $next($request);
    }

    /**
     * Determine the documentation name for the current request.
     *
     * @param  Route|null  $route
     * @return string
     */
    private function resolveDocumentationName(?Route $route): string
    {
        if ($route !== null) {
            $action = $route->getAction();

            if (isset($action['l5-swagger.documentation']) && is_string($action['l5-swagger.documentation'])) {
                return $action['l5-swagger.documentation'];
            }

            $routeName = $route->getName();

            if (is_string($routeName)) {
                $segments = explode('.', $routeName);

                if (count($segments) >= 3 && $segments[1] !== '' && $segments[0] === 'l5-swagger') {
                    return $segments[1];
                }
            }
        }

        return config('l5-swagger.default');
    }

    /**
     * Determine if the route belongs to the Swagger UI ecosystem.
     *
     * @param  Route|null  $route
     * @return bool
     */
    private function isSwaggerRoute(?Route $route): bool
    {
        if ($route === null) {
            return false;
        }

        $name = $route->getName();

        return is_string($name) && str_starts_with($name, 'l5-swagger.');
    }
}
