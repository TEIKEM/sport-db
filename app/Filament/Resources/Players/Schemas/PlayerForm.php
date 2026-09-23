<?php

namespace App\Filament\Resources\Players\Schemas;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class PlayerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('team_id')
                ->relationship('team', 'name'),
                TextInput::make('name')
                    ->required(),
                TextInput::make('position'),
                DatePicker::make('birth_date'),
                TextInput::make('nationality'),
            ]);
    }
}
