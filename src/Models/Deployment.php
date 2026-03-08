<?php

namespace Rylxes\Observability\Models;

use Illuminate\Database\Eloquent\Model;

class Deployment extends Model
{
    protected $guarded = [];

    protected $casts = [
        'metadata' => 'array',
        'deployed_at' => 'datetime',
    ];

    /**
     * Get the table associated with the model.
     */
    public function getTable(): string
    {
        return config('observability.table_prefix', 'observability_') . 'deployments';
    }

    /**
     * Get the current connection name for the model.
     */
    public function getConnectionName(): ?string
    {
        return config('observability.database_connection') ?? config('database.default');
    }

    /**
     * Get the latest deployment.
     */
    public static function latest(): ?self
    {
        return static::orderBy('deployed_at', 'desc')->first();
    }

    /**
     * Get performance metrics in the window after this deployment.
     */
    public function metricsAfter(int $hours = 1): array
    {
        $traces = RequestTrace::where('created_at', '>=', $this->deployed_at)
            ->where('created_at', '<=', $this->deployed_at->copy()->addHours($hours))
            ->get();

        return $this->summarizeTraces($traces);
    }

    /**
     * Get performance metrics in the window before this deployment.
     */
    public function metricsBefore(int $hours = 1): array
    {
        $traces = RequestTrace::where('created_at', '<', $this->deployed_at)
            ->where('created_at', '>=', $this->deployed_at->copy()->subHours($hours))
            ->get();

        return $this->summarizeTraces($traces);
    }

    /**
     * Compare before/after metrics to determine performance impact.
     */
    public function performanceImpact(): array
    {
        $impactWindow = config('observability.deployments.impact_window_hours', 1);
        $before = $this->metricsBefore($impactWindow);
        $after = $this->metricsAfter($impactWindow);

        if (empty($before) || empty($after)) {
            return [
                'status' => 'insufficient_data',
                'before' => $before,
                'after' => $after,
            ];
        }

        return [
            'status' => 'analyzed',
            'before' => $before,
            'after' => $after,
            'changes' => [
                'response_time_change_pct' => $this->percentChange(
                    $before['avg_response_time_ms'] ?? 0,
                    $after['avg_response_time_ms'] ?? 0
                ),
                'error_rate_change_pct' => $this->percentChange(
                    $before['error_rate'] ?? 0,
                    $after['error_rate'] ?? 0
                ),
                'memory_change_pct' => $this->percentChange(
                    $before['avg_memory_mb'] ?? 0,
                    $after['avg_memory_mb'] ?? 0
                ),
                'query_time_change_pct' => $this->percentChange(
                    $before['avg_query_time_ms'] ?? 0,
                    $after['avg_query_time_ms'] ?? 0
                ),
            ],
            'verdict' => $this->determineVerdict(
                $before,
                $after
            ),
        ];
    }

    /**
     * Summarize a collection of traces into performance metrics.
     */
    protected function summarizeTraces($traces): array
    {
        if ($traces->isEmpty()) {
            return [];
        }

        return [
            'total_requests' => $traces->count(),
            'avg_response_time_ms' => round($traces->avg('duration_ms'), 2),
            'avg_memory_mb' => round($traces->avg('memory_usage') / 1024 / 1024, 2),
            'avg_query_count' => round($traces->avg('query_count'), 2),
            'avg_query_time_ms' => round($traces->avg('query_time_ms'), 2),
            'error_rate' => round(($traces->where('status_code', '>=', 400)->count() / $traces->count()) * 100, 2),
        ];
    }

    /**
     * Calculate percent change between two values.
     */
    protected function percentChange(float $before, float $after): float
    {
        if ($before == 0) {
            return $after == 0 ? 0 : 100;
        }

        return round((($after - $before) / $before) * 100, 2);
    }

    /**
     * Determine overall verdict from before/after comparison.
     */
    protected function determineVerdict(array $before, array $after): string
    {
        $responseTimeDelta = $this->percentChange(
            $before['avg_response_time_ms'] ?? 0,
            $after['avg_response_time_ms'] ?? 0
        );
        $errorRateDelta = $this->percentChange(
            $before['error_rate'] ?? 0,
            $after['error_rate'] ?? 0
        );

        if ($responseTimeDelta > 20 || $errorRateDelta > 50) {
            return 'degraded';
        }

        if ($responseTimeDelta < -10) {
            return 'improved';
        }

        return 'neutral';
    }

    /**
     * Scope to filter by environment.
     */
    public function scopeEnvironment($query, string $environment)
    {
        return $query->where('environment', $environment);
    }

    /**
     * Scope to get recent deployments.
     */
    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('deployed_at', '>=', now()->subDays($days));
    }
}
