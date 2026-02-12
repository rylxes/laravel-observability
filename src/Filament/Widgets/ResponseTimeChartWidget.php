<?php

namespace Rylxes\Observability\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Rylxes\Observability\Models\RequestTrace;
use Illuminate\Support\Facades\DB;

class ResponseTimeChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Response Time (24h)';

    protected static ?string $pollingInterval = '60s';

    protected static ?int $sort = 2;

    protected static ?string $maxHeight = '300px';

    protected function getData(): array
    {
        $traces = RequestTrace::where('created_at', '>=', now()->subDay())
            ->selectRaw('
                DATE_FORMAT(created_at, "%Y-%m-%d %H:00") as hour,
                AVG(duration_ms) as avg_duration,
                MAX(duration_ms) as max_duration
            ')
            ->groupBy('hour')
            ->orderBy('hour')
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Avg Response Time (ms)',
                    'data' => $traces->pluck('avg_duration')->map(fn ($v) => round($v, 1))->toArray(),
                    'borderColor' => '#3b82f6',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'fill' => true,
                ],
                [
                    'label' => 'Max Response Time (ms)',
                    'data' => $traces->pluck('max_duration')->map(fn ($v) => round($v, 1))->toArray(),
                    'borderColor' => '#ef4444',
                    'backgroundColor' => 'rgba(239, 68, 68, 0.1)',
                    'fill' => false,
                    'borderDash' => [5, 5],
                ],
            ],
            'labels' => $traces->pluck('hour')->map(fn ($h) => substr($h, 11, 5))->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
