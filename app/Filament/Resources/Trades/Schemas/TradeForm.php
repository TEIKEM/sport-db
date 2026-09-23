<?php

namespace App\Filament\Resources\Trades\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class TradeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('fixture_id')
                    ->required()
                    ->numeric(),
                Toggle::make('is_simulated')
                    ->required(),
                TextInput::make('market')
                    ->required(),
                TextInput::make('selection')
                    ->required(),
                TextInput::make('stake')
                    ->required()
                    ->numeric(),
                TextInput::make('odds_in')
                    ->required()
                    ->numeric(),
                TextInput::make('odds_out')
                    ->numeric(),
                TextInput::make('result')
                    ->required()
                    ->default('OPEN'),
                TextInput::make('profit')
                    ->numeric(),
                Textarea::make('notes')
                    ->columnSpanFull(),
            ]);
    }
}
