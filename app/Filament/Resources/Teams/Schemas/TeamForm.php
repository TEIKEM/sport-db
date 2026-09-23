<?php

namespace App\Filament\Resources\Teams\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
class TeamForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('type')
    ->label('Type')
    ->options(['CLUB' => 'Club', 'NATIONAL' => 'Équipe nationale'])
    ->default('CLUB')
    ->required(),
                Select::make('sport_id')
                ->relationship('sport', 'name')
                ->required(),
                TextInput::make('external_id')
                    ->numeric(),
                TextInput::make('name')
                    ->required(),
                TextInput::make('short_name'),
                TextInput::make('country'),
            ]);
    }
}
