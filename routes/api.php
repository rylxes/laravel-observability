<?php

use Illuminate\Support\Facades\Route;
use Rylxes\Observability\Exporters\PrometheusExporter;
use Rylxes\Observability\Analyzers\PerformanceAnalyzer;
use Rylxes\Observability\Models\RequestTrace;
use Rylxes\Observability\Models\Alert;

/*
|--------------------------------------------------------------------------
| Observability API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('api/observability')
    ->middleware([
        'web',
        'auth:' . implode(',', config('observability.dashboard.guards', ['web', 'sanctum', 'api']))
    ])
    ->group(function () {

        // Prometheus metrics endpoint
        Route::get('/metrics', function (PrometheusExporter $exporter) {
            return response($exporter->export())
                ->header('Content-Type', 'text/plain; version=0.0.4');
        })->name('observability.metrics')->withoutMiddleware('auth');

        // Performance dashboard data
        Route::get('/dashboard', function (PerformanceAnalyzer $analyzer) {
            return response()->json($analyzer->analyze(1));
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

        // Health check
        Route::get('/health', function () {
            return response()->json([
                'status' => 'healthy',
                'enabled' => config('observability.enabled'),
                'version' => '1.0.0',
            ]);
        })->name('observability.health')->withoutMiddleware('auth');
    });
