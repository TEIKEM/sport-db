<?php

namespace App\Filament\Resources\Seasons\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
class SeasonForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('league_id')
                ->relationship('league', 'name'),
                TextInput::make('label')
                    ->required(),
                DatePicker::make('start_date'),
                DatePicker::make('end_date'),
            ]);
    }
}
