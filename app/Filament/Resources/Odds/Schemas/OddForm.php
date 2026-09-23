<?php

namespace App\Filament\Resources\Odds\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class OddForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('fixture_id')
                    ->required()
                    ->numeric(),
                TextInput::make('bookmaker')
                    ->required(),
                TextInput::make('market')
                    ->required(),
                TextInput::make('selection')
                    ->required(),
                TextInput::make('price')
                    ->required()
                    ->numeric()
                    ->prefix('$'),
                DateTimePicker::make('recorded_at')
                    ->required(),
            ]);
    }
}
