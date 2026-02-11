<?php

namespace Rylxes\Observability\Analyzers;

use Illuminate\Support\Facades\DB;
use Rylxes\Observability\Models\QueryLog;
use Rylxes\Observability\Models\Alert;

class SlowQueryDetector
{
    /**
     * Analyze slow queries and generate alerts.
     */
    public function analyze(?int $thresholdMs = null): array
    {
        $thresholdMs = $thresholdMs ?? config('observability.queries.slow_threshold_ms', 1000);

        $slowQueries = QueryLog::slow($thresholdMs)
            ->where('created_at', '>=', now()->subDay())
            ->orderBy('duration_ms', 'desc')
            ->limit(100)
            ->get();

        $insights = [
            'total_slow_queries' => $slowQueries->count(),
            'slowest_query' => null,
            'most_common_slow_table' => null,
            'recommendations' => [],
        ];

        if ($slowQueries->isEmpty()) {
            return $insights;
        }

        // Find slowest query
        $slowest = $slowQueries->first();
        $insights['slowest_query'] = [
            'sql' => $slowest->sql,
            'duration_ms' => $slowest->duration_ms,
            'table' => $slowest->table_name,
        ];

        // Find most common slow table
        $tableCounts = $slowQueries->groupBy('table_name')->map->count()->sortDesc();
        if ($tableCounts->isNotEmpty()) {
            $insights['most_common_slow_table'] = [
                'table' => $tableCounts->keys()->first(),
                'count' => $tableCounts->first(),
            ];
        }

        // Generate recommendations
        $insights['recommendations'] = $this->generateRecommendations($slowQueries);

        // Create alerts for critical slow queries
        $this->createAlertsForSlowQueries($slowQueries);

        return $insights;
    }

    /**
     * Generate optimization recommendations.
     */
    protected function generateRecommendations($slowQueries): array
    {
        $recommendations = [];

        foreach ($slowQueries as $query) {
            $sql = strtoupper($query->sql);

            // Check for missing WHERE clause
            if (str_contains($sql, 'SELECT') && !str_contains($sql, 'WHERE') && !str_contains($sql, 'LIMIT')) {
                $recommendations[] = [
                    'type' => 'missing_where',
                    'message' => "Query on {$query->table_name} has no WHERE clause - consider adding filters",
                    'sql' => $query->sql,
                ];
            }

            // Check for SELECT *
            if (str_contains($sql, 'SELECT *')) {
                $recommendations[] = [
                    'type' => 'select_star',
                    'message' => "Query uses SELECT * - specify only needed columns for better performance",
                    'sql' => $query->sql,
                ];
            }

            // Check for N+1 duplicates
            if ($query->is_duplicate) {
                $recommendations[] = [
                    'type' => 'n_plus_one',
                    'message' => "Duplicate query detected - consider using eager loading",
                    'sql' => $query->sql,
                ];
            }

            // Check for sorting without index
            if (str_contains($sql, 'ORDER BY') && $query->duration_ms > 2000) {
                $recommendations[] = [
                    'type' => 'slow_sorting',
                    'message' => "Slow ORDER BY - ensure proper index exists on sort columns",
                    'sql' => $query->sql,
                ];
            }
        }

        // Remove duplicates
        return array_values(array_unique($recommendations, SORT_REGULAR));
    }

    /**
     * Create alerts for critical slow queries.
     */
    protected function createAlertsForSlowQueries($slowQueries): void
    {
        foreach ($slowQueries as $query) {
            // Only alert for extremely slow queries (10x threshold)
            $criticalThreshold = config('observability.queries.slow_threshold_ms', 1000) * 10;

            if ($query->duration_ms < $criticalThreshold) {
                continue;
            }

            $fingerprint = md5($query->sql);

            // Check if alert already exists (deduplication)
            $existingAlert = Alert::where('fingerprint', $fingerprint)
                ->where('created_at', '>=', now()->subHour())
                ->first();

            if ($existingAlert) {
                continue;
            }

            Alert::create([
                'alert_type' => 'slow_query',
                'severity' => 'warning',
                'title' => 'Critical Slow Query Detected',
                'description' => "Query took {$query->duration_ms}ms to execute",
                'source' => $query->table_name,
                'context' => [
                    'sql' => $query->sql,
                    'duration_ms' => $query->duration_ms,
                    'trace_id' => $query->trace_id,
                ],
                'fingerprint' => $fingerprint,
            ]);
        }
    }

    /**
     * Get slow query statistics by table.
     */
    public function getStatsByTable(int $days = 7): array
    {
        return QueryLog::slow()
            ->where('created_at', '>=', now()->subDays($days))
            ->selectRaw('table_name, COUNT(*) as count, AVG(duration_ms) as avg_duration, MAX(duration_ms) as max_duration')
            ->groupBy('table_name')
            ->orderBy('count', 'desc')
            ->get()
            ->toArray();
    }

    /**
     * Get slow query statistics by type.
     */
    public function getStatsByType(int $days = 7): array
    {
        return QueryLog::slow()
            ->where('created_at', '>=', now()->subDays($days))
            ->selectRaw('query_type, COUNT(*) as count, AVG(duration_ms) as avg_duration')
            ->groupBy('query_type')
            ->orderBy('count', 'desc')
            ->get()
            ->toArray();
    }
}
