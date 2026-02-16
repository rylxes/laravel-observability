<?php

namespace Rylxes\Observability\Support;

class DashboardRouteConfig
{
    /**
     * Resolve the auth guards that can be used with the current application.
     */
    public static function availableGuards(): array
    {
        $configuredGuards = array_keys((array) config('auth.guards', []));

        $desiredGuards = config('observability.dashboard.guards', ['web', 'sanctum']);
        if (!is_array($desiredGuards)) {
            $desiredGuards = [$desiredGuards];
        }

        $desiredGuards = array_values(array_filter($desiredGuards, static fn ($guard) => is_string($guard) && $guard !== ''));

        $availableGuards = array_values(array_intersect($desiredGuards, $configuredGuards));

        return $availableGuards !== [] ? $availableGuards : ['web'];
    }

    /**
     * Check whether authentication is enabled for the dashboard.
     */
    public static function authEnabled(): bool
    {
        return (bool) config('observability.dashboard.auth_enabled', true);
    }

    /**
     * Resolve dashboard middleware, optionally including auth middleware.
     */
    public static function middleware(bool $includeAuth = true): array
    {
        $middleware = config('observability.dashboard.middleware', ['web']);

        if (!is_array($middleware)) {
            $middleware = [$middleware];
        }

        $middleware = array_values(array_filter($middleware, static fn ($item) => is_string($item) && $item !== ''));

        if ($includeAuth && self::authEnabled()) {
            $middleware[] = 'auth:' . implode(',', self::availableGuards());
        }

        return array_values(array_unique($middleware));
    }

    /**
     * Resolve dashboard route prefix.
     */
    public static function routePrefix(): string
    {
        $prefix = config('observability.dashboard.route_prefix', 'admin/observability');

        if (!is_string($prefix)) {
            return 'admin/observability';
        }

        return trim($prefix, '/');
    }
}
