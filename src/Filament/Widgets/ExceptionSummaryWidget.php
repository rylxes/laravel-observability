<?php

namespace Rylxes\Observability\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Rylxes\Observability\Models\ExceptionLog;

class ExceptionSummaryWidget extends BaseWidget
{
    protected static ?string $pollingInterval = '30s';

    protected static ?int $sort = 6;

    protected function getStats(): array
    {
        $recent = ExceptionLog::recent(24);

        $total = (clone $recent)->count();
        $uniqueGroups = (clone $recent)->distinct('group_hash')->count('group_hash');
        $unresolved = (clone $recent)->unresolved()->count();

        $mostFrequent = ExceptionLog::recent(24)
            ->orderBy('occurrence_count', 'desc')
            ->first();

        $mostFrequentLabel = $mostFrequent
            ? class_basename($mostFrequent->exception_class) . ' (' . $mostFrequent->occurrence_count . 'x)'
            : 'None';

        return [
            Stat::make('Exceptions (24h)', $total)
                ->description('Total exception occurrences')
                ->color($total > 0 ? 'danger' : 'success'),

            Stat::make('Unique Groups', $uniqueGroups)
                ->description('Distinct exception types')
                ->color($uniqueGroups > 0 ? 'warning' : 'success'),

            Stat::make('Unresolved', $unresolved)
                ->description('Awaiting resolution')
                ->color($unresolved > 0 ? 'danger' : 'success'),

            Stat::make('Most Frequent', $mostFrequentLabel)
                ->description('Highest occurrence count')
                ->color($mostFrequent ? 'warning' : 'gray'),
        ];
    }
}
