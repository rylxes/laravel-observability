<?php

namespace Rylxes\Observability\Console\Commands;

use Illuminate\Console\Command;
use Rylxes\Observability\Models\RequestTrace;
use Rylxes\Observability\Models\QueryLog;
use Rylxes\Observability\Models\PerformanceMetric;
use Rylxes\Observability\Models\Alert;

class PruneMetricsCommand extends Command
{
    protected $signature = 'observability:prune {--force : Skip confirmation}';
    protected $description = 'Prune old observability data based on retention settings';

    public function handle(): int
    {
        if (!$this->option('force') && !$this->confirm('This will delete old observability data. Continue?')) {
            return self::SUCCESS;
        }

        $this->info('Pruning old observability data...');

        // Prune traces
        $tracesDays = config('observability.retention.traces_days', 7);
        $tracesDeleted = RequestTrace::where('created_at', '<', now()->subDays($tracesDays))->delete();
        $this->info("✓ Deleted {$tracesDeleted} old traces (>{$tracesDays} days)");

        // Prune queries
        $queriesDays = config('observability.retention.queries_days', 7);
        $queriesDeleted = QueryLog::where('created_at', '<', now()->subDays($queriesDays))->delete();
        $this->info("✓ Deleted {$queriesDeleted} old queries (>{$queriesDays} days)");

        // Prune metrics
        $metricsDays = config('observability.retention.metrics_days', 30);
        $metricsDeleted = PerformanceMetric::where('created_at', '<', now()->subDays($metricsDays))->delete();
        $this->info("✓ Deleted {$metricsDeleted} old metrics (>{$metricsDays} days)");

        // Prune alerts
        $alertsDays = config('observability.retention.alerts_days', 30);
        $alertsDeleted = Alert::where('created_at', '<', now()->subDays($alertsDays))->delete();
        $this->info("✓ Deleted {$alertsDeleted} old alerts (>{$alertsDays} days)");

        $this->info('✓ Pruning complete!');

        return self::SUCCESS;
    }
}
