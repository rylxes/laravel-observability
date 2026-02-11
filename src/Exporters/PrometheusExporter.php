<?php

namespace Rylxes\Observability\Exporters;

use Prometheus\CollectorRegistry;
use Prometheus\Storage\InMemory;
use Prometheus\Storage\Redis;
use Prometheus\Storage\APC;
use Prometheus\RenderTextFormat;
use Rylxes\Observability\Models\RequestTrace;
use Rylxes\Observability\Models\QueryLog;

class PrometheusExporter
{
    protected CollectorRegistry $registry;
    protected string $namespace;

    public function __construct()
    {
        $this->namespace = config('observability.exporters.prometheus.namespace', 'laravel');
        $this->registry = $this->createRegistry();
    }

    /**
     * Create Prometheus registry with configured storage adapter.
     */
    protected function createRegistry(): CollectorRegistry
    {
        $adapter = match (config('observability.exporters.prometheus.storage_adapter', 'memory')) {
            'redis' => new Redis([
                'host' => config('database.redis.default.host', '127.0.0.1'),
                'port' => config('database.redis.default.port', 6379),
                'password' => config('database.redis.default.password'),
                'database' => config('database.redis.default.database', 0),
                'prefix' => config('observability.exporters.prometheus.redis_prefix', 'PROMETHEUS_'),
            ]),
            'apc' => new APC(),
            default => new InMemory(),
        };

        return new CollectorRegistry($adapter);
    }

    /**
     * Export metrics in Prometheus format.
     */
    public function export(): string
    {
        if (!config('observability.exporters.prometheus.enabled')) {
            return '';
        }

        // Register metrics
        $this->registerMetrics();

        // Render metrics
        $renderer = new RenderTextFormat();
        return $renderer->render($this->registry->getMetricFamilySamples());
    }

    /**
     * Register application metrics.
     */
    protected function registerMetrics(): void
    {
        // HTTP request duration histogram
        $requestDuration = $this->registry->getOrRegisterHistogram(
            $this->namespace,
            'http_request_duration_milliseconds',
            'HTTP request duration in milliseconds',
            ['method', 'route', 'status_code'],
            [50, 100, 250, 500, 1000, 2500, 5000, 10000]
        );

        // HTTP request counter
        $requestCounter = $this->registry->getOrRegisterCounter(
            $this->namespace,
            'http_requests_total',
            'Total HTTP requests',
            ['method', 'route', 'status_code']
        );

        // Database query duration histogram
        $queryDuration = $this->registry->getOrRegisterHistogram(
            $this->namespace,
            'db_query_duration_milliseconds',
            'Database query duration in milliseconds',
            ['query_type', 'table'],
            [10, 50, 100, 500, 1000, 5000]
        );

        // Memory usage gauge
        $memoryGauge = $this->registry->getOrRegisterGauge(
            $this->namespace,
            'memory_usage_bytes',
            'Memory usage in bytes',
            ['route']
        );

        // Populate metrics from recent data
        $this->populateMetrics($requestDuration, $requestCounter, $queryDuration, $memoryGauge);
    }

    /**
     * Populate metrics with data from database.
     */
    protected function populateMetrics($requestDuration, $requestCounter, $queryDuration, $memoryGauge): void
    {
        // Get recent traces (last hour)
        $traces = RequestTrace::where('created_at', '>=', now()->subHour())->get();

        foreach ($traces as $trace) {
            $labels = [
                'method' => $trace->method,
                'route' => $trace->route_name ?? 'unknown',
                'status_code' => (string) $trace->status_code,
            ];

            // Record request duration
            $requestDuration->observe($trace->duration_ms, array_values($labels));

            // Increment request counter
            $requestCounter->inc(array_values($labels));

            // Record memory usage
            $memoryGauge->set($trace->memory_usage, [$trace->route_name ?? 'unknown']);
        }

        // Get recent queries
        $queries = QueryLog::where('created_at', '>=', now()->subHour())->get();

        foreach ($queries as $query) {
            $queryDuration->observe($query->duration_ms, [
                $query->query_type ?? 'UNKNOWN',
                $query->table_name ?? 'unknown',
            ]);
        }
    }

    /**
     * Get metrics as array (for API responses).
     */
    public function getMetricsArray(): array
    {
        $traces = RequestTrace::where('created_at', '>=', now()->subHour())->get();

        return [
            'total_requests' => $traces->count(),
            'avg_response_time' => round($traces->avg('duration_ms'), 2),
            'error_count' => $traces->where('status_code', '>=', 400)->count(),
            'avg_memory_usage' => round($traces->avg('memory_usage'), 2),
        ];
    }
}
