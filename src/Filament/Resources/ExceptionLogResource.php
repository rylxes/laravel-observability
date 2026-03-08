<?php

namespace Rylxes\Observability\Filament\Resources;

use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Rylxes\Observability\Models\ExceptionLog;
use Rylxes\Observability\Filament\Resources\ExceptionLogResource\Pages;

class ExceptionLogResource extends Resource
{
    protected static ?string $model = ExceptionLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-exclamation-triangle';

    protected static ?string $navigationGroup = 'Observability';

    protected static ?string $navigationLabel = 'Exceptions';

    protected static ?int $navigationSort = 4;

    public static function getNavigationBadge(): ?string
    {
        $count = ExceptionLog::unresolved()->recent(24)->grouped()->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $critical = ExceptionLog::unresolved()->where('severity', 'critical')->recent(24)->count();

        return $critical > 0 ? 'danger' : 'warning';
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('exception_class')
                    ->label('Exception')
                    ->formatStateUsing(fn (string $state) => class_basename($state))
                    ->description(fn (ExceptionLog $record) => \Illuminate\Support\Str::limit($record->message, 60))
                    ->searchable(),

                Tables\Columns\TextColumn::make('severity')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'critical' => 'danger',
                        'error' => 'danger',
                        'warning' => 'warning',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('occurrence_count')
                    ->label('Count')
                    ->sortable()
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('file')
                    ->label('Location')
                    ->formatStateUsing(fn (ExceptionLog $record) => basename($record->file) . ':' . $record->line)
                    ->toggleable(),

                Tables\Columns\IconColumn::make('resolved')
                    ->boolean(),

                Tables\Columns\TextColumn::make('first_seen_at')
                    ->label('First Seen')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('last_seen_at')
                    ->label('Last Seen')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('last_seen_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('severity')
                    ->options([
                        'critical' => 'Critical',
                        'error' => 'Error',
                        'warning' => 'Warning',
                    ]),

                Tables\Filters\Filter::make('unresolved')
                    ->query(fn ($query) => $query->where('resolved', false))
                    ->label('Unresolved')
                    ->default(),

                Tables\Filters\Filter::make('last_24h')
                    ->query(fn ($query) => $query->where('created_at', '>=', now()->subDay()))
                    ->label('Last 24 Hours'),
            ])
            ->actions([
                Tables\Actions\Action::make('resolve')
                    ->action(fn (ExceptionLog $record) => $record->markResolved())
                    ->requiresConfirmation()
                    ->visible(fn (ExceptionLog $record) => !$record->resolved)
                    ->icon('heroicon-o-check-circle')
                    ->color('success'),
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('resolve')
                    ->action(fn ($records) => $records->each->markResolved())
                    ->requiresConfirmation()
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->deselectRecordsAfterCompletion(),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Exception Details')
                    ->schema([
                        Infolists\Components\TextEntry::make('exception_class')
                            ->label('Class')
                            ->copyable(),
                        Infolists\Components\TextEntry::make('severity')
                            ->badge()
                            ->color(fn (string $state) => match ($state) {
                                'critical' => 'danger',
                                'error' => 'danger',
                                'warning' => 'warning',
                                default => 'gray',
                            }),
                        Infolists\Components\TextEntry::make('message')
                            ->columnSpanFull(),
                        Infolists\Components\TextEntry::make('file'),
                        Infolists\Components\TextEntry::make('line'),
                        Infolists\Components\TextEntry::make('code')
                            ->placeholder('N/A'),
                    ])->columns(2),

                Infolists\Components\Section::make('Occurrence Info')
                    ->schema([
                        Infolists\Components\TextEntry::make('occurrence_count')
                            ->label('Total Occurrences'),
                        Infolists\Components\TextEntry::make('first_seen_at')
                            ->dateTime(),
                        Infolists\Components\TextEntry::make('last_seen_at')
                            ->dateTime(),
                        Infolists\Components\TextEntry::make('group_hash')
                            ->copyable()
                            ->toggleable(isToggledHiddenByDefault: true),
                        Infolists\Components\IconEntry::make('resolved')
                            ->boolean(),
                        Infolists\Components\TextEntry::make('resolved_at')
                            ->dateTime()
                            ->placeholder('Not resolved'),
                    ])->columns(3),

                Infolists\Components\Section::make('Stack Trace')
                    ->schema([
                        Infolists\Components\TextEntry::make('stack_trace')
                            ->formatStateUsing(fn ($state) => is_string($state) ? json_encode(json_decode($state, true), JSON_PRETTY_PRINT) : json_encode($state, JSON_PRETTY_PRINT))
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),

                Infolists\Components\Section::make('Context')
                    ->schema([
                        Infolists\Components\TextEntry::make('context')
                            ->formatStateUsing(fn ($state) => json_encode($state, JSON_PRETTY_PRINT))
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),

                Infolists\Components\Section::make('Related Trace')
                    ->schema([
                        Infolists\Components\TextEntry::make('trace_id')
                            ->copyable()
                            ->placeholder('No trace linked'),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListExceptionLogs::route('/'),
            'view' => Pages\ViewExceptionLog::route('/{record}'),
        ];
    }
}
