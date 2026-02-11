<?php

namespace Rylxes\Observability\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

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
        $this->warn('IMPORTANT: Add the middleware to your app/Http/Kernel.php:');
        $this->line('');
        $this->line("protected \$middleware = [");
        $this->line("    // ... other middleware");
        $this->line("    \\YourVendor\\Observability\\Middleware\\RequestTracingMiddleware::class,");
        $this->line("];");
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
        $this->newLine();

        $this->info('✓ Installation complete!');
        $this->info('📊 Visit /api/observability/metrics to view your metrics');

        return self::SUCCESS;
    }
}
