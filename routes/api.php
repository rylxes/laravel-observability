<?php

use Illuminate\Support\Facades\Route;
use Rylxes\Observability\Exporters\PrometheusExporter;
use Rylxes\Observability\Analyzers\PerformanceAnalyzer;
use Rylxes\Observability\Models\RequestTrace;
use Rylxes\Observability\Models\Alert;
use Rylxes\Observability\Models\ExceptionLog;
use Rylxes\Observability\Models\Deployment;
use Rylxes\Observability\Support\DashboardRouteConfig;

/*
|--------------------------------------------------------------------------
| Observability API Routes
|--------------------------------------------------------------------------
*/

$apiMiddleware = ['web'];
if (DashboardRouteConfig::authEnabled()) {
    $apiMiddleware[] = 'auth:' . implode(',', DashboardRouteConfig::availableGuards());
}

Route::prefix('api/observability')
    ->middleware(array_values(array_unique($apiMiddleware)))
    ->group(function () {

        // Prometheus metrics endpoint
        Route::get('/metrics', function (PrometheusExporter $exporter) {
            return response($exporter->export())
                ->header('Content-Type', 'text/plain; version=0.0.4');
        })->name('observability.metrics')->withoutMiddleware('auth');

        // Performance dashboard data
        Route::get('/dashboard', function (PerformanceAnalyzer $analyzer) {
            $days = max(1, min((int) request()->query('days', 1), 30));

            return response()->json($analyzer->analyze($days));
        })->name('observability.dashboard');

        // Recent traces
        Route::get('/traces', function () {
            return response()->json(
                RequestTrace::with('queries')
                    ->orderBy('created_at', 'desc')
                    ->limit(100)
                    ->get()
            );
        })->name('observability.traces');

        // Single trace detail
        Route::get('/traces/{traceId}', function (string $traceId) {
            $trace = RequestTrace::where('trace_id', $traceId)
                ->with('queries')
                ->firstOrFail();

            return response()->json($trace);
        })->name('observability.trace');

        // Alerts
        Route::get('/alerts', function () {
            return response()->json(
                Alert::orderBy('created_at', 'desc')
                    ->limit(100)
                    ->get()
            );
        })->name('observability.alerts');

        // Resolve alert
        Route::post('/alerts/{alert}/resolve', function (Alert $alert) {
            $alert->markResolved();
            return response()->json(['message' => 'Alert resolved']);
        })->name('observability.alerts.resolve');

        // Exceptions (grouped)
        Route::get('/exceptions', function () {
            return response()->json(
                ExceptionLog::grouped()
                    ->orderBy('last_seen_at', 'desc')
                    ->limit(100)
                    ->get()
            );
        })->name('observability.exceptions');

        // Exception group detail
        Route::get('/exceptions/{groupHash}', function (string $groupHash) {
            return response()->json(
                ExceptionLog::where('group_hash', $groupHash)
                    ->orderBy('created_at', 'desc')
                    ->limit(50)
                    ->get()
            );
        })->name('observability.exceptions.group');

        // Resolve exception
        Route::post('/exceptions/{exceptionLog}/resolve', function (ExceptionLog $exceptionLog) {
            $exceptionLog->markResolved();
            return response()->json(['message' => 'Exception marked as resolved']);
        })->name('observability.exceptions.resolve');

        // Deployments
        Route::post('/deployments', function () {
            $data = request()->validate([
                'version' => 'nullable|string|max:50',
                'description' => 'nullable|string',
                'commit_hash' => 'nullable|string|max:40',
                'branch' => 'nullable|string|max:100',
                'deployer' => 'nullable|string|max:100',
                'environment' => 'nullable|string|max:50',
                'metadata' => 'nullable|array',
            ]);

            $deployment = Deployment::create(array_merge($data, [
                'environment' => $data['environment'] ?? config('app.env', 'production'),
                'deployed_at' => now(),
            ]));

            return response()->json($deployment, 201);
        })->name('observability.deployments.store');

        Route::get('/deployments', function () {
            return response()->json(
                Deployment::orderBy('deployed_at', 'desc')
                    ->limit(50)
                    ->get()
            );
        })->name('observability.deployments');

        Route::get('/deployments/{deployment}/impact', function (Deployment $deployment) {
            return response()->json($deployment->performanceImpact());
        })->name('observability.deployments.impact');

        // Health check
        Route::get('/health', function () {
            return response()->json([
                'status' => 'healthy',
                'enabled' => config('observability.enabled'),
                'version' => '1.0.0',
            ]);
        })->name('observability.health')->withoutMiddleware('auth');
    });
