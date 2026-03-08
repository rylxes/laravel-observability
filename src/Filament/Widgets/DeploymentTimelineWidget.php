<?php

namespace Rylxes\Observability\Filament\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Rylxes\Observability\Models\Deployment;

class DeploymentTimelineWidget extends BaseWidget
{
    protected static ?string $heading = 'Recent Deployments';

    protected static ?int $sort = 7;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Deployment::query()->orderBy('deployed_at', 'desc')->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('version')
                    ->label('Version')
                    ->badge()
                    ->color('primary')
                    ->default('—'),

                Tables\Columns\TextColumn::make('environment')
                    ->label('Env')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'production' => 'danger',
                        'staging' => 'warning',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('branch')
                    ->label('Branch')
                    ->default('—'),

                Tables\Columns\TextColumn::make('commit_hash')
                    ->label('Commit')
                    ->limit(8)
                    ->default('—'),

                Tables\Columns\TextColumn::make('deployer')
                    ->label('Deployer')
                    ->default('—'),

                Tables\Columns\TextColumn::make('deployed_at')
                    ->label('Deployed At')
                    ->dateTime()
                    ->sortable(),

                Tables\Columns\TextColumn::make('impact')
                    ->label('Impact')
                    ->getStateUsing(function (Deployment $record) {
                        $impact = $record->performanceImpact();
                        return $impact['verdict'] ?? $impact['status'] ?? 'unknown';
                    })
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'improved' => 'success',
                        'neutral' => 'gray',
                        'degraded' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->paginated(false);
    }
}
