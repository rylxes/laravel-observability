<?php

namespace Rylxes\Observability\Filament\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Rylxes\Observability\Models\RequestTrace;
use Rylxes\Observability\Filament\Resources\RequestTraceResource\Pages;

class RequestTraceResource extends Resource
{
    protected static ?string $model = RequestTrace::class;

    protected static ?string $navigationIcon = 'heroicon-o-signal';

    protected static ?string $navigationGroup = 'Observability';

    protected static ?string $navigationLabel = 'Traces';

    protected static ?int $navigationSort = 1;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('trace_id')
                    ->label('Trace ID')
                    ->limit(12)
                    ->copyable()
                    ->searchable(),

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
                    ->limit(50)
                    ->searchable()
                    ->tooltip(fn (RequestTrace $record) => $record->url),

                Tables\Columns\TextColumn::make('route_name')
                    ->label('Route')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('status_code')
                    ->label('Status')
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
                    ->sortable()
                    ->color(fn (int $state) => $state > 3000 ? 'danger' : ($state > 1000 ? 'warning' : 'success')),

                Tables\Columns\TextColumn::make('query_count')
                    ->label('Queries')
                    ->sortable(),

                Tables\Columns\TextColumn::make('memory_usage')
                    ->label('Memory')
                    ->formatStateUsing(fn (int $state) => round($state / 1024 / 1024, 1) . ' MB')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Time')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('method')
                    ->options([
                        'GET' => 'GET',
                        'POST' => 'POST',
                        'PUT' => 'PUT',
                        'PATCH' => 'PATCH',
                        'DELETE' => 'DELETE',
                    ]),

                Tables\Filters\Filter::make('slow')
                    ->query(fn ($query) => $query->where('duration_ms', '>=', config('observability.performance.thresholds.response_time_ms', 3000)))
                    ->label('Slow Requests'),

                Tables\Filters\Filter::make('errors')
                    ->query(fn ($query) => $query->where('status_code', '>=', 400))
                    ->label('Errors Only'),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Request Details')
                    ->schema([
                        Infolists\Components\TextEntry::make('trace_id')->copyable(),
                        Infolists\Components\TextEntry::make('parent_trace_id'),
                        Infolists\Components\TextEntry::make('method')->badge(),
                        Infolists\Components\TextEntry::make('url'),
                        Infolists\Components\TextEntry::make('route_name'),
                        Infolists\Components\TextEntry::make('route_action'),
                        Infolists\Components\TextEntry::make('status_code')->badge(),
                        Infolists\Components\TextEntry::make('ip_address'),
                        Infolists\Components\TextEntry::make('user_agent'),
                        Infolists\Components\TextEntry::make('user_id'),
                    ])->columns(2),

                Infolists\Components\Section::make('Performance')
                    ->schema([
                        Infolists\Components\TextEntry::make('duration_ms')
                            ->suffix(' ms')
                            ->label('Duration'),
                        Infolists\Components\TextEntry::make('memory_usage')
                            ->formatStateUsing(fn (int $state) => round($state / 1024 / 1024, 2) . ' MB'),
                        Infolists\Components\TextEntry::make('query_count'),
                        Infolists\Components\TextEntry::make('query_time_ms')
                            ->suffix(' ms')
                            ->label('Query Time'),
                    ])->columns(4),

                Infolists\Components\Section::make('Metadata')
                    ->schema([
                        Infolists\Components\TextEntry::make('metadata')
                            ->formatStateUsing(fn ($state) => json_encode($state, JSON_PRETTY_PRINT))
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRequestTraces::route('/'),
            'view' => Pages\ViewRequestTrace::route('/{record}'),
        ];
    }
}
