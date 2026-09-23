<?php

namespace App\Filament\Resources\Fixtures\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class FixturesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('league.name')->label('Ligue')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('season.label')->label('Saison')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('source')
                    ->searchable(),
                TextColumn::make('external_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('homeTeam.name')->label('Domicile')
                ->sortable()
                ->searchable(),
                TextColumn::make('awayTeam.name')->label('Extérieur')
                ->sortable()
                ->searchable(),
                TextColumn::make('kickoff_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('status')
                    ->searchable(),
                TextColumn::make('home_score')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('away_score')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('ht_home_score')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('ht_away_score')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('referee.name')->label('Arbitre')
                ->sortable()
                ->searchable(),
                TextColumn::make('venue')
                    ->searchable(),
                TextColumn::make('matchday')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('weather')
                    ->searchable(),
                TextColumn::make('attendance')
                    ->numeric()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                \Filament\Actions\Action::make('saisir_stats')
    ->label('Saisir les stats')
    ->icon('heroicon-o-chart-bar')
    ->url(fn ($record) => \App\Filament\Resources\FixtureStats\FixtureStatResource::getUrl('create', ['fixture_id' => $record->id])),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
