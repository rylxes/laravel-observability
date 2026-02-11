<?php

namespace Rylxes\Observability;

use Illuminate\Support\Facades\App;
use Rylxes\Observability\Analyzers\PerformanceAnalyzer;
use Rylxes\Observability\Analyzers\SlowQueryDetector;
use Rylxes\Observability\Analyzers\AnomalyDetector;
use Rylxes\Observability\Exporters\PrometheusExporter;

class ObservabilityManager
{
    public function __construct(protected $app)
    {
    }

    /**
     * Get performance analyzer instance.
     */
    public function performance(): PerformanceAnalyzer
    {
        return App::make(PerformanceAnalyzer::class);
    }

    /**
     * Get slow query detector instance.
     */
    public function slowQueries(): SlowQueryDetector
    {
        return App::make(SlowQueryDetector::class);
    }

    /**
     * Get anomaly detector instance.
     */
    public function anomalies(): AnomalyDetector
    {
        return App::make(AnomalyDetector::class);
    }

    /**
     * Get Prometheus exporter instance.
     */
    public function prometheus(): PrometheusExporter
    {
        return App::make(PrometheusExporter::class);
    }

    /**
     * Quick performance analysis.
     */
    public function analyze(int $days = 1): array
    {
        return $this->performance()->analyze($days);
    }

    /**
     * Export metrics.
     */
    public function exportMetrics(): string
    {
        return $this->prometheus()->export();
    }
}
