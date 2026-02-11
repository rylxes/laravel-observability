<?php

namespace Rylxes\Observability\Console\Commands;

use Illuminate\Console\Command;
use Rylxes\Observability\Analyzers\PerformanceAnalyzer;
use Rylxes\Observability\Analyzers\SlowQueryDetector;
use Rylxes\Observability\Analyzers\AnomalyDetector;
use Rylxes\Observability\Notifications\SlackNotifier;
use Rylxes\Observability\Notifications\TelegramNotifier;
use Rylxes\Observability\Models\Alert;

class AnalyzePerformanceCommand extends Command
{
    protected $signature = 'observability:analyze
                            {--days=1 : Number of days to analyze}
                            {--notify : Send notifications for issues}';
    protected $description = 'Analyze application performance and detect anomalies';

    public function __construct(
        protected PerformanceAnalyzer $performance,
        protected SlowQueryDetector $slowQueries,
        protected AnomalyDetector $anomalies,
        protected SlackNotifier $slack,
        protected TelegramNotifier $telegram
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $this->info("Analyzing performance for the last {$days} day(s)...");

        // Performance Analysis
        $this->newLine();
        $this->info('🔍 Performance Analysis:');
        $perfAnalysis = $this->performance->analyze($days);

        if (isset($perfAnalysis['overall_metrics'])) {
            $metrics = $perfAnalysis['overall_metrics'];
            $this->table(
                ['Metric', 'Value'],
                [
                    ['Total Requests', $metrics['total_requests'] ?? 0],
                    ['Avg Response Time', ($metrics['avg_response_time_ms'] ?? 0) . ' ms'],
                    ['P95 Response Time', ($metrics['p95_response_time_ms'] ?? 0) . ' ms'],
                    ['P99 Response Time', ($metrics['p99_response_time_ms'] ?? 0) . ' ms'],
                    ['Avg Memory', ($metrics['avg_memory_mb'] ?? 0) . ' MB'],
                    ['Error Rate', ($metrics['error_rate'] ?? 0) . '%'],
                ]
            );
        }

        // Slow Queries
        $this->newLine();
        $this->info('🐌 Slow Query Analysis:');
        $slowQueryAnalysis = $this->slowQueries->analyze();
        $this->line("Total slow queries: {$slowQueryAnalysis['total_slow_queries']}");

        if ($slowQueryAnalysis['slowest_query']) {
            $this->warn("Slowest query: {$slowQueryAnalysis['slowest_query']['duration_ms']} ms");
        }

        // Anomaly Detection
        if (config('observability.anomaly_detection.enabled')) {
            $this->newLine();
            $this->info('🤖 AI Anomaly Detection:');
            $anomalyResult = $this->anomalies->detectAnomalies('response_time');

            if ($anomalyResult['status'] === 'success') {
                $count = $anomalyResult['anomalies_detected'];
                if ($count > 0) {
                    $this->warn("⚠️ {$count} anomalies detected!");
                } else {
                    $this->info('✓ No anomalies detected');
                }
            } else {
                $this->line($anomalyResult['message']);
            }
        }

        // Send notifications if requested
        if ($this->option('notify')) {
            $this->notifyIssues();
        }

        $this->newLine();
        $this->info('✓ Analysis complete!');

        return self::SUCCESS;
    }

    /**
     * Send notifications for pending alerts.
     */
    protected function notifyIssues(): void
    {
        $pendingAlerts = Alert::pendingNotification()->get();

        if ($pendingAlerts->isEmpty()) {
            $this->info('No pending alerts to notify');
            return;
        }

        $this->info("Sending {$pendingAlerts->count()} notifications...");

        foreach ($pendingAlerts as $alert) {
            $channels = [];

            if (config('observability.notifications.slack.enabled')) {
                if ($this->slack->notify($alert)) {
                    $channels[] = 'slack';
                }
            }

            if (config('observability.notifications.telegram.enabled')) {
                if ($this->telegram->notify($alert)) {
                    $channels[] = 'telegram';
                }
            }

            if (!empty($channels)) {
                $alert->markNotified($channels);
            }
        }

        $this->info('✓ Notifications sent');
    }
}
