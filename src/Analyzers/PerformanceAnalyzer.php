<?php

namespace Rylxes\Observability\Analyzers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Rylxes\Observability\Models\RequestTrace;
use Rylxes\Observability\Models\PerformanceMetric;
use Rylxes\Observability\Models\Alert;
use Rylxes\Observability\Events\PerformanceThresholdExceeded;

class PerformanceAnalyzer
{
    /**
     * Analyze overall application performance.
     */
    public function analyze(int $days = 1): array
    {
        $cacheKey = config('observability.cache.prefix') . "performance_analysis_{$days}d";
        $cacheTtl = config('observability.cache.ttl_seconds', 300);

        if (config('observability.cache.enabled')) {
            $cached = Cache::get($cacheKey);
            if ($cached) {
                return $cached;
            }
        }

        $analysis = [
            'overall_metrics' => $this->getOverallMetrics($days),
            'route_performance' => $this->getRoutePerformance($days),
            'error_analysis' => $this->getErrorAnalysis($days),
            'performance_trends' => $this->getPerformanceTrends($days),
            'bottlenecks' => $this->identifyBottlenecks($days),
        ];

        if (config('observability.cache.enabled')) {
            Cache::put($cacheKey, $analysis, $cacheTtl);
        }

        return $analysis;
    }

    /**
     * Get overall performance metrics.
     */
    protected function getOverallMetrics(int $days): array
    {
        $traces = RequestTrace::where('created_at', '>=', now()->subDays($days))->get();

        if ($traces->isEmpty()) {
            return [];
        }

        return [
            'total_requests' => $traces->count(),
            'avg_response_time_ms' => round($traces->avg('duration_ms'), 2),
            'p50_response_time_ms' => $this->percentile($traces->pluck('duration_ms')->toArray(), 50),
            'p95_response_time_ms' => $this->percentile($traces->pluck('duration_ms')->toArray(), 95),
            'p99_response_time_ms' => $this->percentile($traces->pluck('duration_ms')->toArray(), 99),
            'avg_memory_mb' => round($traces->avg('memory_usage') / 1024 / 1024, 2),
            'avg_query_count' => round($traces->avg('query_count'), 2),
            'avg_query_time_ms' => round($traces->avg('query_time_ms'), 2),
            'error_rate' => round(($traces->where('status_code', '>=', 400)->count() / $traces->count()) * 100, 2),
        ];
    }

    /**
     * Get performance metrics per route.
     */
    protected function getRoutePerformance(int $days): array
    {
        return RequestTrace::where('created_at', '>=', now()->subDays($days))
            ->whereNotNull('route_name')
            ->selectRaw('
                route_name,
                COUNT(*) as request_count,
                AVG(duration_ms) as avg_duration,
                MAX(duration_ms) as max_duration,
                AVG(memory_usage) as avg_memory,
                AVG(query_count) as avg_queries,
                SUM(CASE WHEN status_code >= 400 THEN 1 ELSE 0 END) as error_count
            ')
            ->groupBy('route_name')
            ->orderBy('request_count', 'desc')
            ->limit(20)
            ->get()
            ->map(function ($route) {
                return [
                    'route' => $route->route_name,
                    'requests' => $route->request_count,
                    'avg_duration_ms' => round($route->avg_duration, 2),
                    'max_duration_ms' => round($route->max_duration, 2),
                    'avg_memory_mb' => round($route->avg_memory / 1024 / 1024, 2),
                    'avg_queries' => round($route->avg_queries, 2),
                    'error_rate' => $route->request_count > 0
                        ? round(($route->error_count / $route->request_count) * 100, 2)
                        : 0,
                ];
            })
            ->toArray();
    }

    /**
     * Analyze error patterns.
     */
    protected function getErrorAnalysis(int $days): array
    {
        $errors = RequestTrace::errors()
            ->where('created_at', '>=', now()->subDays($days))
            ->get();

        $statusGroups = $errors->groupBy('status_code')->map->count();

        return [
            'total_errors' => $errors->count(),
            'by_status_code' => $statusGroups->toArray(),
            'top_error_routes' => $errors->groupBy('route_name')
                ->map->count()
                ->sortDesc()
                ->take(10)
                ->toArray(),
        ];
    }

    /**
     * Get performance trends over time.
     */
    protected function getPerformanceTrends(int $days): array
    {
        $hourlyStats = RequestTrace::where('created_at', '>=', now()->subDays($days))
            ->selectRaw('
                DATE_FORMAT(created_at, "%Y-%m-%d %H:00:00") as hour,
                COUNT(*) as requests,
                AVG(duration_ms) as avg_duration,
                SUM(CASE WHEN status_code >= 400 THEN 1 ELSE 0 END) as errors
            ')
            ->groupBy('hour')
            ->orderBy('hour')
            ->get()
            ->map(function ($stat) {
                return [
                    'timestamp' => $stat->hour,
                    'requests' => $stat->requests,
                    'avg_duration_ms' => round($stat->avg_duration, 2),
                    'errors' => $stat->errors,
                ];
            })
            ->toArray();

        return $hourlyStats;
    }

    /**
     * Identify performance bottlenecks.
     */
    protected function identifyBottlenecks(int $days): array
    {
        $bottlenecks = [];

        // Routes with high response time
        $slowRoutes = RequestTrace::where('created_at', '>=', now()->subDays($days))
            ->whereNotNull('route_name')
            ->selectRaw('route_name, AVG(duration_ms) as avg_duration')
            ->groupBy('route_name')
            ->having('avg_duration', '>', config('observability.performance.thresholds.response_time_ms', 3000))
            ->get();

        if ($slowRoutes->isNotEmpty()) {
            $bottlenecks[] = [
                'type' => 'slow_routes',
                'severity' => 'warning',
                'message' => 'Routes exceeding response time threshold',
                'data' => $slowRoutes->pluck('route_name')->toArray(),
            ];
        }

        // High memory usage
        $memoryHogs = RequestTrace::where('created_at', '>=', now()->subDays($days))
            ->whereNotNull('route_name')
            ->selectRaw('route_name, AVG(memory_usage) as avg_memory')
            ->groupBy('route_name')
            ->having('avg_memory', '>', config('observability.performance.thresholds.memory_usage_mb', 256) * 1024 * 1024)
            ->get();

        if ($memoryHogs->isNotEmpty()) {
            $bottlenecks[] = [
                'type' => 'high_memory',
                'severity' => 'error',
                'message' => 'Routes with excessive memory usage',
                'data' => $memoryHogs->pluck('route_name')->toArray(),
            ];
        }

        // High query count (N+1 problems)
        $queryHeavy = RequestTrace::where('created_at', '>=', now()->subDays($days))
            ->whereNotNull('route_name')
            ->selectRaw('route_name, AVG(query_count) as avg_queries')
            ->groupBy('route_name')
            ->having('avg_queries', '>', config('observability.performance.thresholds.query_count', 50))
            ->get();

        if ($queryHeavy->isNotEmpty()) {
            $bottlenecks[] = [
                'type' => 'excessive_queries',
                'severity' => 'warning',
                'message' => 'Routes with high query count (possible N+1)',
                'data' => $queryHeavy->pluck('route_name')->toArray(),
            ];
        }

        // Broadcast real-time events for each bottleneck
        if (config('observability.broadcasting.enabled') && !empty($bottlenecks)) {
            foreach ($bottlenecks as $bottleneck) {
                event(new PerformanceThresholdExceeded(
                    type: $bottleneck['type'],
                    severity: $bottleneck['severity'],
                    message: $bottleneck['message'],
                    routes: $bottleneck['data'],
                ));
            }
        }

        return $bottlenecks;
    }

    /**
     * Calculate percentile from array of values.
     */
    protected function percentile(array $values, int $percentile): float
    {
        if (empty($values)) {
            return 0;
        }

        sort($values);
        $index = ceil(count($values) * ($percentile / 100)) - 1;
        $index = max(0, min($index, count($values) - 1));

        return round($values[$index], 2);
    }

    /**
     * Store aggregated metrics for long-term analysis.
     */
    public function storeAggregatedMetrics(string $period = '1h'): void
    {
        $periodStart = match ($period) {
            '1h' => now()->subHour(),
            '1d' => now()->subDay(),
            '7d' => now()->subDays(7),
            '30d' => now()->subDays(30),
            default => now()->subHour(),
        };

        $traces = RequestTrace::where('created_at', '>=', $periodStart)->get();

        if ($traces->isEmpty()) {
            return;
        }

        // Store response time metric
        PerformanceMetric::create([
            'metric_type' => 'response_time',
            'metric_name' => 'global',
            'value' => $traces->avg('duration_ms'),
            'aggregation_period' => $period,
            'period_start' => $periodStart,
            'period_end' => now(),
        ]);

        // Store per-route metrics
        $traces->groupBy('route_name')->each(function ($routeTraces, $routeName) use ($period, $periodStart) {
            if (!$routeName) return;

            PerformanceMetric::create([
                'metric_type' => 'response_time',
                'metric_name' => $routeName,
                'value' => $routeTraces->avg('duration_ms'),
                'aggregation_period' => $period,
                'period_start' => $periodStart,
                'period_end' => now(),
            ]);
        });
    }
}
