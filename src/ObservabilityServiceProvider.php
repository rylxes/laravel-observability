<?php

namespace Rylxes\Observability;

use Illuminate\Support\ServiceProvider;
use Illuminate\Routing\Router;
use Rylxes\Observability\Middleware\RequestTracingMiddleware;
use Rylxes\Observability\Console\Commands\InstallCommand;
use Rylxes\Observability\Console\Commands\PruneMetricsCommand;
use Rylxes\Observability\Console\Commands\AnalyzePerformanceCommand;
use Rylxes\Observability\Collectors\DatabaseQueryCollector;
use Rylxes\Observability\Analyzers\SlowQueryDetector;
use Rylxes\Observability\Analyzers\PerformanceAnalyzer;
use Rylxes\Observability\Analyzers\AnomalyDetector;
use Rylxes\Observability\Exporters\PrometheusExporter;
use Rylxes\Observability\Notifications\SlackNotifier;
use Rylxes\Observability\Notifications\TelegramNotifier;

class ObservabilityServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Merge configuration
        $this->mergeConfigFrom(
            __DIR__ . '/../config/observability.php',
            'observability'
        );

        // Register singletons
        $this->app->singleton(DatabaseQueryCollector::class);
        $this->app->singleton(SlowQueryDetector::class);
        $this->app->singleton(PerformanceAnalyzer::class);
        $this->app->singleton(AnomalyDetector::class);
        $this->app->singleton(PrometheusExporter::class);
        $this->app->singleton(SlackNotifier::class);
        $this->app->singleton(TelegramNotifier::class);

        // Register facade accessor
        $this->app->singleton('observability', function ($app) {
            return new ObservabilityManager($app);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Publish configuration
        $this->publishes([
            __DIR__ . '/../config/observability.php' => config_path('observability.php'),
        ], 'observability-config');

        // Publish migrations
        $this->publishes([
            __DIR__ . '/../database/migrations' => database_path('migrations'),
        ], 'observability-migrations');

        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        // Load routes
        $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');

        // Register middleware
        $this->registerMiddleware();

        // Register commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallCommand::class,
                PruneMetricsCommand::class,
                AnalyzePerformanceCommand::class,
            ]);
        }

        // Schedule tasks
        $this->scheduleCommands();
    }

    /**
     * Register middleware.
     */
    protected function registerMiddleware(): void
    {
        $router = $this->app->make(Router::class);

        // Register middleware alias
        $router->aliasMiddleware('observability', RequestTracingMiddleware::class);

        // Auto-register middleware globally if enabled
        if (config('observability.enabled') && config('observability.tracing.enabled')) {
            $router->pushMiddlewareToGroup('web', RequestTracingMiddleware::class);
            $router->pushMiddlewareToGroup('api', RequestTracingMiddleware::class);
        }
    }

    /**
     * Schedule periodic tasks.
     */
    protected function scheduleCommands(): void
    {
        if (!$this->app->runningInConsole()) {
            return;
        }

        $this->app->booted(function () {
            $schedule = $this->app->make(\Illuminate\Console\Scheduling\Schedule::class);

            // Prune old metrics daily
            $schedule->command('observability:prune')->daily();

            // Analyze performance hourly
            if (config('observability.anomaly_detection.enabled')) {
                $schedule->command('observability:analyze')->hourly();
            }
        });
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [
            'observability',
            DatabaseQueryCollector::class,
            SlowQueryDetector::class,
            PerformanceAnalyzer::class,
            AnomalyDetector::class,
            PrometheusExporter::class,
            SlackNotifier::class,
            TelegramNotifier::class,
        ];
    }
}
