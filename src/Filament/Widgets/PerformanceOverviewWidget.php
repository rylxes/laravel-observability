<?php

namespace Rylxes\Observability\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Rylxes\Observability\Models\RequestTrace;

class PerformanceOverviewWidget extends BaseWidget
{
    protected static ?string $pollingInterval = '30s';

    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $traces = RequestTrace::where('created_at', '>=', now()->subDay())->get();

        if ($traces->isEmpty()) {
            return [
                Stat::make('Total Requests (24h)', 0),
                Stat::make('Avg Response Time', '0 ms'),
                Stat::make('P95 Response Time', '0 ms'),
                Stat::make('Error Rate', '0%'),
            ];
        }

        $totalRequests = $traces->count();
        $avgResponseTime = round($traces->avg('duration_ms'), 1);
        $errorCount = $traces->where('status_code', '>=', 400)->count();
        $errorRate = round(($errorCount / $totalRequests) * 100, 1);

        $durations = $traces->pluck('duration_ms')->sort()->values()->toArray();
        $p95Index = max(0, (int) ceil(count($durations) * 0.95) - 1);
        $p95 = round($durations[$p95Index] ?? 0, 1);

        return [
            Stat::make('Total Requests (24h)', number_format($totalRequests))
                ->description($totalRequests > 0 ? 'Last 24 hours' : 'No data')
                ->color('primary'),

            Stat::make('Avg Response Time', $avgResponseTime . ' ms')
                ->description('Average duration')
                ->color($avgResponseTime > 1000 ? 'danger' : ($avgResponseTime > 500 ? 'warning' : 'success')),

            Stat::make('P95 Response Time', $p95 . ' ms')
                ->description('95th percentile')
                ->color($p95 > 3000 ? 'danger' : ($p95 > 1000 ? 'warning' : 'success')),

            Stat::make('Error Rate', $errorRate . '%')
                ->description($errorCount . ' errors')
                ->color($errorRate > 5 ? 'danger' : ($errorRate > 1 ? 'warning' : 'success')),
        ];
    }
}
