<?php

namespace Rylxes\Observability\Filament\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Rylxes\Observability\Models\Alert;
use Rylxes\Observability\Filament\Resources\AlertResource\Pages;

class AlertResource extends Resource
{
    protected static ?string $model = Alert::class;

    protected static ?string $navigationIcon = 'heroicon-o-bell-alert';

    protected static ?string $navigationGroup = 'Observability';

    protected static ?string $navigationLabel = 'Alerts';

    protected static ?int $navigationSort = 3;

    public static function getNavigationBadge(): ?string
    {
        $count = Alert::unresolved()->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $critical = Alert::unresolved()->where('severity', 'critical')->count();

        return $critical > 0 ? 'danger' : 'warning';
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->limit(50),

                Tables\Columns\TextColumn::make('alert_type')
                    ->label('Type')
                    ->badge(),

                Tables\Columns\TextColumn::make('severity')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'critical' => 'danger',
                        'error' => 'danger',
                        'warning' => 'warning',
                        'info' => 'info',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('source')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\IconColumn::make('notified')
                    ->boolean(),

                Tables\Columns\IconColumn::make('resolved')
                    ->boolean(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Time')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('severity')
                    ->options([
                        'critical' => 'Critical',
                        'error' => 'Error',
                        'warning' => 'Warning',
                        'info' => 'Info',
                    ]),

                Tables\Filters\SelectFilter::make('alert_type')
                    ->options([
                        'anomaly' => 'Anomaly',
                        'slow_query' => 'Slow Query',
                        'high_memory' => 'High Memory',
                        'error_spike' => 'Error Spike',
                    ]),

                Tables\Filters\Filter::make('unresolved')
                    ->query(fn ($query) => $query->where('resolved', false))
                    ->label('Unresolved')
                    ->default(),
            ])
            ->actions([
                Tables\Actions\Action::make('resolve')
                    ->action(fn (Alert $record) => $record->markResolved())
                    ->requiresConfirmation()
                    ->visible(fn (Alert $record) => !$record->resolved)
                    ->icon('heroicon-o-check-circle')
                    ->color('success'),
                Tables\Actions\ViewAction::make(),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Alert Details')
                    ->schema([
                        Infolists\Components\TextEntry::make('title'),
                        Infolists\Components\TextEntry::make('description')
                            ->columnSpanFull(),
                        Infolists\Components\TextEntry::make('alert_type')->badge(),
                        Infolists\Components\TextEntry::make('severity')
                            ->badge()
                            ->color(fn (string $state) => match ($state) {
                                'critical' => 'danger',
                                'error' => 'danger',
                                'warning' => 'warning',
                                default => 'info',
                            }),
                        Infolists\Components\TextEntry::make('source'),
                        Infolists\Components\TextEntry::make('fingerprint')
                            ->copyable()
                            ->toggleable(isToggledHiddenByDefault: true),
                    ])->columns(2),

                Infolists\Components\Section::make('Status')
                    ->schema([
                        Infolists\Components\IconEntry::make('notified')->boolean(),
                        Infolists\Components\TextEntry::make('notified_at')->dateTime(),
                        Infolists\Components\TextEntry::make('notification_channels'),
                        Infolists\Components\IconEntry::make('resolved')->boolean(),
                        Infolists\Components\TextEntry::make('resolved_at')->dateTime(),
                    ])->columns(3),

                Infolists\Components\Section::make('Context')
                    ->schema([
                        Infolists\Components\TextEntry::make('context')
                            ->formatStateUsing(fn ($state) => json_encode($state, JSON_PRETTY_PRINT))
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAlerts::route('/'),
            'view' => Pages\ViewAlert::route('/{record}'),
        ];
    }
}
