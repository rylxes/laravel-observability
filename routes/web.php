<?php

use Illuminate\Support\Facades\Route;
use Rylxes\Observability\Http\Controllers\ObservabilityDashboardController;
use Rylxes\Observability\Support\DashboardRouteConfig;

/*
|--------------------------------------------------------------------------
| Observability Web Routes
|--------------------------------------------------------------------------
*/

if (config('observability.dashboard.enabled', true)) {
    $dashboardRoute = Route::middleware(DashboardRouteConfig::middleware());

    $routePrefix = DashboardRouteConfig::routePrefix();
    if ($routePrefix !== '') {
        $dashboardRoute->prefix($routePrefix);
    }

    $dashboardRoute->group(function (): void {
        Route::get('/', ObservabilityDashboardController::class)
            ->name('observability.ui');
    });
}
