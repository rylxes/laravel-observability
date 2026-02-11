<?php

namespace Rylxes\Observability\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Rylxes\Observability\Analyzers\PerformanceAnalyzer performance()
 * @method static \Rylxes\Observability\Analyzers\SlowQueryDetector slowQueries()
 * @method static \Rylxes\Observability\Analyzers\AnomalyDetector anomalies()
 * @method static \Rylxes\Observability\Exporters\PrometheusExporter prometheus()
 * @method static array analyze(int $days = 1)
 * @method static string exportMetrics()
 *
 * @see \Rylxes\Observability\ObservabilityManager
 */
class Observability extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'observability';
    }
}
