<?php

namespace App\Filament\Resources\Odds;

use App\Filament\Resources\Odds\Pages\CreateOdd;
use App\Filament\Resources\Odds\Pages\EditOdd;
use App\Filament\Resources\Odds\Pages\ListOdds;
use App\Filament\Resources\Odds\Schemas\OddForm;
use App\Filament\Resources\Odds\Tables\OddsTable;
use App\Models\Odd;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class OddResource extends Resource
{
    protected static ?string $model = Odd::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return OddForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OddsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOdds::route('/'),
            'create' => CreateOdd::route('/create'),
            'edit' => EditOdd::route('/{record}/edit'),
        ];
    }
}
