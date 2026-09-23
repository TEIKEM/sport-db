<?php

namespace App\Filament\Resources\Leagues\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;

class LeagueForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('type')
    ->label('Type')
    ->options([
        'LEAGUE' => 'Championnat',
        'CUP' => 'Coupe nationale',
        'CONTINENTAL_CLUB' => 'Compétition de clubs (continentale/mondiale)',
        'INTERNATIONAL' => 'Compétition de sélections nationales',
    ])
    ->default('LEAGUE')
    ->required(),
                Select::make('sport_id')
    ->relationship('sport', 'name')
    ->searchable()
    ->preload()
    ->required(),
                TextInput::make('external_id')
                    ->numeric(),
                TextInput::make('name')
                    ->required(),
                TextInput::make('country'),
                TextInput::make('code'),
            ]);
    }
}
