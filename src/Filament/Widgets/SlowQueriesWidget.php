<?php

namespace Rylxes\Observability\Filament\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Rylxes\Observability\Models\QueryLog;

class SlowQueriesWidget extends BaseWidget
{
    protected static ?string $heading = 'Recent Slow Queries';

    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                QueryLog::query()
                    ->where('is_slow', true)
                    ->orderBy('created_at', 'desc')
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('sql')
                    ->label('SQL')
                    ->limit(80)
                    ->tooltip(fn (QueryLog $record) => $record->sql),

                Tables\Columns\TextColumn::make('duration_ms')
                    ->label('Duration')
                    ->suffix(' ms')
                    ->sortable()
                    ->color(fn (int $state) => $state > 5000 ? 'danger' : ($state > 2000 ? 'warning' : 'primary')),

                Tables\Columns\TextColumn::make('table_name')
                    ->label('Table'),

                Tables\Columns\TextColumn::make('query_type')
                    ->label('Type')
                    ->badge(),

                Tables\Columns\IconColumn::make('is_duplicate')
                    ->label('N+1')
                    ->boolean(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Time')
                    ->since(),
            ])
            ->paginated(false);
    }
}
