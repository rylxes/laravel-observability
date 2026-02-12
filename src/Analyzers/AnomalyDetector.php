<?php

namespace Rylxes\Observability\Analyzers;

use Illuminate\Support\Collection;
use Rylxes\Observability\Models\PerformanceMetric;
use Rylxes\Observability\Models\RequestTrace;
use Rylxes\Observability\Models\Alert;
use Rylxes\Observability\Events\AnomalyDetected;

class AnomalyDetector
{
    /**
     * Detect anomalies using statistical analysis (Z-score method).
     */
    public function detectAnomalies(string $metricType = 'response_time'): array
    {
        if (!config('observability.anomaly_detection.enabled')) {
            return [];
        }

        $baselineWindowDays = config('observability.anomaly_detection.baseline_window_days', 7);
        $zScoreThreshold = config('observability.anomaly_detection.z_score_threshold', 3.0);
        $minDataPoints = config('observability.anomaly_detection.min_data_points', 100);

        // Get historical data for baseline
        $baselineData = $this->getBaselineData($metricType, $baselineWindowDays);

        if ($baselineData->count() < $minDataPoints) {
            return [
                'status' => 'insufficient_data',
                'message' => "Need at least {$minDataPoints} data points for anomaly detection",
                'current_count' => $baselineData->count(),
            ];
        }

        // Calculate baseline statistics
        $baseline = $this->calculateBaseline($baselineData);

        // Get recent data to check for anomalies
        $recentData = $this->getRecentData($metricType);

        // Detect anomalies
        $anomalies = [];
        foreach ($recentData as $dataPoint) {
            $zScore = $this->calculateZScore($dataPoint, $baseline);

            if (abs($zScore) >= $zScoreThreshold) {
                $anomaly = [
                    'metric_type' => $metricType,
                    'metric_name' => $dataPoint->route_name ?? 'global',
                    'value' => $dataPoint->value,
                    'baseline' => $baseline['mean'],
                    'z_score' => $zScore,
                    'deviation_percent' => round((($dataPoint->value - $baseline['mean']) / $baseline['mean']) * 100, 2),
                    'timestamp' => $dataPoint->created_at,
                ];

                $anomalies[] = $anomaly;

                // Store anomaly metric
                $this->storeAnomalyMetric($anomaly, $baseline);

                // Create alert
                $this->createAnomalyAlert($anomaly);
            }
        }

        return [
            'status' => 'success',
            'baseline' => $baseline,
            'anomalies_detected' => count($anomalies),
            'anomalies' => $anomalies,
        ];
    }

    /**
     * Get baseline data for statistical analysis.
     */
    protected function getBaselineData(string $metricType, int $days): Collection
    {
        return match ($metricType) {
            'response_time' => RequestTrace::where('created_at', '>=', now()->subDays($days))
                ->where('created_at', '<', now()->subHour()) // Exclude very recent data
                ->select('duration_ms as value', 'route_name', 'created_at')
                ->get(),

            'memory_usage' => RequestTrace::where('created_at', '>=', now()->subDays($days))
                ->where('created_at', '<', now()->subHour())
                ->select('memory_usage as value', 'route_name', 'created_at')
                ->get(),

            'error_rate' => $this->calculateErrorRates($days),

            default => collect(),
        };
    }

    /**
     * Get recent data to check for anomalies.
     */
    protected function getRecentData(string $metricType): Collection
    {
        return match ($metricType) {
            'response_time' => RequestTrace::where('created_at', '>=', now()->subHour())
                ->select('duration_ms as value', 'route_name', 'created_at')
                ->get(),

            'memory_usage' => RequestTrace::where('created_at', '>=', now()->subHour())
                ->select('memory_usage as value', 'route_name', 'created_at')
                ->get(),

            'error_rate' => $this->calculateErrorRates(1, true),

            default => collect(),
        };
    }

    /**
     * Calculate baseline statistics (mean and standard deviation).
     */
    protected function calculateBaseline(Collection $data): array
    {
        $values = $data->pluck('value')->filter()->toArray();

        if (empty($values)) {
            return [
                'mean' => 0,
                'stddev' => 0,
                'count' => 0,
            ];
        }

        $mean = array_sum($values) / count($values);
        $variance = array_sum(array_map(fn($x) => pow($x - $mean, 2), $values)) / count($values);
        $stddev = sqrt($variance);

        return [
            'mean' => round($mean, 2),
            'stddev' => round($stddev, 2),
            'count' => count($values),
            'min' => min($values),
            'max' => max($values),
        ];
    }

    /**
     * Calculate Z-score for a data point.
     */
    protected function calculateZScore($dataPoint, array $baseline): float
    {
        if ($baseline['stddev'] == 0) {
            return 0;
        }

        $value = is_object($dataPoint) ? $dataPoint->value : $dataPoint;
        $zScore = ($value - $baseline['mean']) / $baseline['stddev'];

        return round($zScore, 4);
    }

    /**
     * Calculate error rates for baseline and recent data.
     */
    protected function calculateErrorRates(int $days, bool $recentOnly = false): Collection
    {
        $query = RequestTrace::query();

        if ($recentOnly) {
            $query->where('created_at', '>=', now()->subHour());
        } else {
            $query->where('created_at', '>=', now()->subDays($days))
                  ->where('created_at', '<', now()->subHour());
        }

        $stats = $query->selectRaw('
                route_name,
                COUNT(*) as total_requests,
                SUM(CASE WHEN status_code >= 400 THEN 1 ELSE 0 END) as error_count,
                created_at
            ')
            ->groupBy('route_name', 'created_at')
            ->get()
            ->map(function ($stat) {
                return (object) [
                    'route_name' => $stat->route_name,
                    'value' => $stat->total_requests > 0
                        ? ($stat->error_count / $stat->total_requests) * 100
                        : 0,
                    'created_at' => $stat->created_at,
                ];
            });

        return $stats;
    }

    /**
     * Store anomaly metric for historical tracking.
     */
    protected function storeAnomalyMetric(array $anomaly, array $baseline): void
    {
        PerformanceMetric::create([
            'metric_type' => $anomaly['metric_type'],
            'metric_name' => $anomaly['metric_name'],
            'value' => $anomaly['value'],
            'baseline' => $baseline['mean'],
            'z_score' => $anomaly['z_score'],
            'is_anomaly' => true,
            'aggregation_period' => '1h',
            'metadata' => [
                'deviation_percent' => $anomaly['deviation_percent'],
                'baseline_stddev' => $baseline['stddev'],
            ],
        ]);
    }

    /**
     * Create alert for detected anomaly.
     */
    protected function createAnomalyAlert(array $anomaly): void
    {
        $fingerprint = md5($anomaly['metric_type'] . $anomaly['metric_name']);

        // Check for recent similar alerts (deduplication)
        $recentAlert = Alert::where('fingerprint', $fingerprint)
            ->where('alert_type', 'anomaly')
            ->where('created_at', '>=', now()->subHour())
            ->first();

        if ($recentAlert) {
            return;
        }

        $severity = abs($anomaly['z_score']) > 5 ? 'critical' : 'warning';

        Alert::create([
            'alert_type' => 'anomaly',
            'severity' => $severity,
            'title' => 'Performance Anomaly Detected',
            'description' => "Unusual {$anomaly['metric_type']} detected for {$anomaly['metric_name']}",
            'source' => $anomaly['metric_name'],
            'context' => $anomaly,
            'fingerprint' => $fingerprint,
        ]);

        // Broadcast real-time event
        if (config('observability.broadcasting.enabled')) {
            event(new AnomalyDetected(
                metricType: $anomaly['metric_type'],
                metricName: $anomaly['metric_name'],
                value: $anomaly['value'],
                baseline: $anomaly['baseline'],
                zScore: $anomaly['z_score'],
                deviationPercent: $anomaly['deviation_percent'],
            ));
        }
    }

    /**
     * Detect trends using linear regression.
     */
    public function detectTrend(string $metricType, int $days = 7): array
    {
        $data = $this->getBaselineData($metricType, $days);

        if ($data->count() < 2) {
            return [
                'trend' => 'unknown',
                'slope' => 0,
            ];
        }

        // Simple linear regression
        $values = $data->pluck('value')->toArray();
        $n = count($values);
        $x = range(0, $n - 1);

        $sumX = array_sum($x);
        $sumY = array_sum($values);
        $sumXY = array_sum(array_map(fn($i) => $x[$i] * $values[$i], array_keys($x)));
        $sumX2 = array_sum(array_map(fn($val) => $val * $val, $x));

        $slope = ($n * $sumXY - $sumX * $sumY) / ($n * $sumX2 - $sumX * $sumX);

        $trend = match (true) {
            $slope > 0.1 => 'increasing',
            $slope < -0.1 => 'decreasing',
            default => 'stable',
        };

        return [
            'trend' => $trend,
            'slope' => round($slope, 4),
            'interpretation' => $this->interpretTrend($metricType, $slope),
        ];
    }

    /**
     * Interpret trend based on metric type.
     */
    protected function interpretTrend(string $metricType, float $slope): string
    {
        if (abs($slope) < 0.1) {
            return 'Metric is stable';
        }

        return match ($metricType) {
            'response_time', 'memory_usage', 'error_rate' => $slope > 0
                ? 'Warning: Metric is increasing over time'
                : 'Good: Metric is decreasing over time',

            default => $slope > 0 ? 'Increasing trend' : 'Decreasing trend',
        };
    }
}
