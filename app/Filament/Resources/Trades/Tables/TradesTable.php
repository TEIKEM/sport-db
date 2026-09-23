<?php

namespace App\Filament\Resources\Trades\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TradesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('fixture.homeTeam.name')->label('Domicile')
                ->sortable()
                ->searchable(),
                 TextColumn::make('fixture.awayTeam.name')->label('Domicile')
                ->sortable()
                ->searchable(),
                IconColumn::make('is_simulated')
                    ->boolean(),
                TextColumn::make('market')
                    ->searchable(),
                TextColumn::make('selection')
                    ->searchable(),
                TextColumn::make('stake')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('odds_in')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('odds_out')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('result')
                    ->searchable(),
                TextColumn::make('profit')
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
