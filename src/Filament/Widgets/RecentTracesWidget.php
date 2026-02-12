<?php

namespace Rylxes\Observability\Filament\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Rylxes\Observability\Models\RequestTrace;

class RecentTracesWidget extends BaseWidget
{
    protected static ?string $heading = 'Recent Traces';

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                RequestTrace::query()
                    ->orderBy('created_at', 'desc')
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('method')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'GET' => 'info',
                        'POST' => 'success',
                        'PUT', 'PATCH' => 'warning',
                        'DELETE' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('url')
                    ->limit(60)
                    ->tooltip(fn (RequestTrace $record) => $record->url),

                Tables\Columns\TextColumn::make('status_code')
                    ->badge()
                    ->color(fn (int $state) => match (true) {
                        $state >= 500 => 'danger',
                        $state >= 400 => 'warning',
                        $state >= 300 => 'info',
                        default => 'success',
                    }),

                Tables\Columns\TextColumn::make('duration_ms')
                    ->label('Duration')
                    ->suffix(' ms')
                    ->color(fn (int $state) => $state > 3000 ? 'danger' : ($state > 1000 ? 'warning' : 'success')),

                Tables\Columns\TextColumn::make('query_count')
                    ->label('Queries'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Time')
                    ->since(),
            ])
            ->paginated(false);
    }
}
