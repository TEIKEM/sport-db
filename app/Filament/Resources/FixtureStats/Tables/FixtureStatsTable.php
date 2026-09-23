<?php

namespace App\Filament\Resources\FixtureStats\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class FixtureStatsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('fixture.homeTeam.name')->label('Domicile')
                ->sortable()
                ->searchable(),
                 TextColumn::make('fixture.awayTeam.name')->label('Exterieur')
                ->sortable()
                ->searchable(),
                TextColumn::make('team.name')->label('Équipe')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('possession')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('shots')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('shots_on_target')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('corners')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('fouls')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('yellow_cards')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('red_cards')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('xg')
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
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
