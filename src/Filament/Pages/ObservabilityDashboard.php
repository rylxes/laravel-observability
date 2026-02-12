<?php

namespace Rylxes\Observability\Filament\Pages;

use Filament\Pages\Dashboard;
use Rylxes\Observability\Filament\Widgets\PerformanceOverviewWidget;
use Rylxes\Observability\Filament\Widgets\AlertSummaryWidget;
use Rylxes\Observability\Filament\Widgets\ResponseTimeChartWidget;
use Rylxes\Observability\Filament\Widgets\RecentTracesWidget;
use Rylxes\Observability\Filament\Widgets\SlowQueriesWidget;

class ObservabilityDashboard extends Dashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationGroup = 'Observability';

    protected static ?string $navigationLabel = 'Dashboard';

    protected static ?int $navigationSort = 0;

    protected static ?string $title = 'Observability Dashboard';

    protected static string $routePath = 'observability';

    public function getWidgets(): array
    {
        return [
            PerformanceOverviewWidget::class,
            AlertSummaryWidget::class,
            ResponseTimeChartWidget::class,
            RecentTracesWidget::class,
            SlowQueriesWidget::class,
        ];
    }

    public function getColumns(): int|string|array
    {
        return 2;
    }
}
