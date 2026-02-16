<?php

namespace Rylxes\Observability\Console\Commands;

use Illuminate\Console\Command;

class InstallCommand extends Command
{
    protected $signature = 'observability:install {--force : Overwrite existing files}';
    protected $description = 'Install Laravel Observability Plugin';

    public function handle(): int
    {
        $this->info('Installing Laravel Observability Plugin...');

        // Publish configuration
        $this->call('vendor:publish', [
            '--tag' => 'observability-config',
            '--force' => $this->option('force'),
        ]);

        $this->info('✓ Configuration published');

        // Run migrations
        if ($this->confirm('Run migrations now?', true)) {
            $this->call('migrate');
            $this->info('✓ Migrations executed');
        }

        // Add middleware instructions
        $this->newLine();
        $this->warn('IMPORTANT: If you want to manually register the middleware (instead of auto-registration):');

        $laravelVersion = (int) app()->version();

        if ($laravelVersion >= 11) {
            $this->line('');
            $this->line('Add to your bootstrap/app.php:');
            $this->line('');
            $this->line('->withMiddleware(function (Middleware $middleware) {');
            $this->line('    $middleware->append(\\Rylxes\\Observability\\Middleware\\RequestTracingMiddleware::class);');
            $this->line('})');
        } else {
            $this->line('');
            $this->line('Add to your app/Http/Kernel.php:');
            $this->line('');
            $this->line('protected $middleware = [');
            $this->line('    // ... other middleware');
            $this->line('    \\Rylxes\\Observability\\Middleware\\RequestTracingMiddleware::class,');
            $this->line('];');
        }

        $this->newLine();

        // Environment variables
        $this->info('Add these to your .env file:');
        $this->line('');
        $this->line('# Observability Configuration');
        $this->line('OBSERVABILITY_ENABLED=true');
        $this->line('OBSERVABILITY_TRACING_ENABLED=true');
        $this->line('OBSERVABILITY_SLOW_QUERY_THRESHOLD=1000');
        $this->line('');
        $this->line('# Optional: Slack Notifications');
        $this->line('OBSERVABILITY_SLACK_ENABLED=false');
        $this->line('OBSERVABILITY_SLACK_WEBHOOK_URL=');
        $this->line('');
        $this->line('# Optional: Telegram Notifications');
        $this->line('OBSERVABILITY_TELEGRAM_ENABLED=false');
        $this->line('OBSERVABILITY_TELEGRAM_BOT_TOKEN=');
        $this->line('OBSERVABILITY_TELEGRAM_CHAT_ID=');
        $this->line('');
        $this->line('# Optional: Prometheus Export');
        $this->line('OBSERVABILITY_PROMETHEUS_ENABLED=false');
        $this->line('');
        $this->line('# Optional: Dashboard UI');
        $this->line('OBSERVABILITY_DASHBOARD_PREFIX=admin/observability');
        $this->line('OBSERVABILITY_DASHBOARD_REFRESH_INTERVAL=30');
        $this->newLine();

        $this->info('✓ Installation complete!');
        $dashboardPath = trim((string) config('observability.dashboard.route_prefix', 'admin/observability'), '/');
        $dashboardPath = $dashboardPath === '' ? '/' : '/' . $dashboardPath;
        $this->info("🎛 Visit {$dashboardPath} for the dashboard UI (authenticated)");
        $this->info('📊 Visit /api/observability/metrics to view your metrics');

        return self::SUCCESS;
    }
}
