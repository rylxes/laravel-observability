<?php

namespace Rylxes\Observability\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Rylxes\Observability\Filament\Pages\ObservabilityDashboard;
use Rylxes\Observability\Filament\Resources\RequestTraceResource;
use Rylxes\Observability\Filament\Resources\QueryLogResource;
use Rylxes\Observability\Filament\Resources\AlertResource;
use Rylxes\Observability\Filament\Widgets\PerformanceOverviewWidget;
use Rylxes\Observability\Filament\Widgets\ResponseTimeChartWidget;
use Rylxes\Observability\Filament\Widgets\SlowQueriesWidget;
use Rylxes\Observability\Filament\Widgets\RecentTracesWidget;
use Rylxes\Observability\Filament\Widgets\AlertSummaryWidget;

class ObservabilityPlugin implements Plugin
{
    public static function make(): static
    {
        return app(static::class);
    }

    public static function get(): static
    {
        return filament(app(static::class)->getId());
    }

    public function getId(): string
    {
        return 'observability';
    }

    public function register(Panel $panel): void
    {
        if (!config('observability.dashboard.enabled', true)) {
            return;
        }

        $panel
            ->pages([
                ObservabilityDashboard::class,
            ])
            ->resources([
                RequestTraceResource::class,
                QueryLogResource::class,
                AlertResource::class,
            ])
            ->widgets([
                PerformanceOverviewWidget::class,
                ResponseTimeChartWidget::class,
                SlowQueriesWidget::class,
                RecentTracesWidget::class,
                AlertSummaryWidget::class,
            ]);
    }

    public function boot(Panel $panel): void
    {
        //
    }
}
