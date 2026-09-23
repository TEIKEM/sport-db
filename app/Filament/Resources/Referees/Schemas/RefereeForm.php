<?php

namespace App\Filament\Resources\Referees\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class RefereeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('country'),
            ]);
    }
}
