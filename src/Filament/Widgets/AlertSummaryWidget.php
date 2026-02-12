<?php

namespace Rylxes\Observability\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Rylxes\Observability\Models\Alert;

class AlertSummaryWidget extends BaseWidget
{
    protected static ?string $pollingInterval = '30s';

    protected static ?int $sort = 5;

    protected function getStats(): array
    {
        $unresolved = Alert::unresolved();

        $critical = (clone $unresolved)->where('severity', 'critical')->count();
        $error = (clone $unresolved)->where('severity', 'error')->count();
        $warning = (clone $unresolved)->where('severity', 'warning')->count();
        $total = $critical + $error + $warning;

        return [
            Stat::make('Unresolved Alerts', $total)
                ->description('Total open alerts')
                ->color($critical > 0 ? 'danger' : ($warning > 0 ? 'warning' : 'success')),

            Stat::make('Critical', $critical)
                ->description('Critical severity')
                ->color($critical > 0 ? 'danger' : 'gray'),

            Stat::make('Errors', $error)
                ->description('Error severity')
                ->color($error > 0 ? 'danger' : 'gray'),

            Stat::make('Warnings', $warning)
                ->description('Warning severity')
                ->color($warning > 0 ? 'warning' : 'gray'),
        ];
    }
}
