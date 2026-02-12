<?php

namespace Rylxes\Observability\Filament\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Rylxes\Observability\Models\QueryLog;
use Rylxes\Observability\Filament\Resources\QueryLogResource\Pages;

class QueryLogResource extends Resource
{
    protected static ?string $model = QueryLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-circle-stack';

    protected static ?string $navigationGroup = 'Observability';

    protected static ?string $navigationLabel = 'Queries';

    protected static ?int $navigationSort = 2;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('sql')
                    ->label('SQL')
                    ->limit(70)
                    ->searchable()
                    ->tooltip(fn (QueryLog $record) => $record->sql),

                Tables\Columns\TextColumn::make('duration_ms')
                    ->label('Duration')
                    ->suffix(' ms')
                    ->sortable()
                    ->color(fn (int $state) => $state > 5000 ? 'danger' : ($state > 1000 ? 'warning' : 'primary')),

                Tables\Columns\TextColumn::make('query_type')
                    ->label('Type')
                    ->badge(),

                Tables\Columns\TextColumn::make('table_name')
                    ->label('Table')
                    ->searchable(),

                Tables\Columns\IconColumn::make('is_slow')
                    ->label('Slow')
                    ->boolean(),

                Tables\Columns\IconColumn::make('is_duplicate')
                    ->label('N+1')
                    ->boolean(),

                Tables\Columns\TextColumn::make('connection_name')
                    ->label('Connection')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Time')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\Filter::make('slow')
                    ->query(fn ($query) => $query->where('is_slow', true))
                    ->label('Slow Queries'),

                Tables\Filters\Filter::make('duplicates')
                    ->query(fn ($query) => $query->where('is_duplicate', true))
                    ->label('Duplicates (N+1)'),

                Tables\Filters\SelectFilter::make('query_type')
                    ->options([
                        'SELECT' => 'SELECT',
                        'INSERT' => 'INSERT',
                        'UPDATE' => 'UPDATE',
                        'DELETE' => 'DELETE',
                    ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Query Details')
                    ->schema([
                        Infolists\Components\TextEntry::make('sql')
                            ->columnSpanFull(),
                        Infolists\Components\TextEntry::make('bindings')
                            ->formatStateUsing(fn ($state) => json_encode($state, JSON_PRETTY_PRINT))
                            ->columnSpanFull(),
                        Infolists\Components\TextEntry::make('duration_ms')->suffix(' ms'),
                        Infolists\Components\TextEntry::make('query_type')->badge(),
                        Infolists\Components\TextEntry::make('table_name'),
                        Infolists\Components\TextEntry::make('connection_name'),
                        Infolists\Components\IconEntry::make('is_slow')->boolean(),
                        Infolists\Components\IconEntry::make('is_duplicate')->boolean(),
                    ])->columns(2),

                Infolists\Components\Section::make('Trace')
                    ->schema([
                        Infolists\Components\TextEntry::make('trace_id')->copyable(),
                    ]),

                Infolists\Components\Section::make('Stack Trace')
                    ->schema([
                        Infolists\Components\TextEntry::make('stack_trace')
                            ->formatStateUsing(fn ($state) => $state ? json_encode(json_decode($state), JSON_PRETTY_PRINT) : 'N/A')
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListQueryLogs::route('/'),
            'view' => Pages\ViewQueryLog::route('/{record}'),
        ];
    }
}
