<?php

namespace App\Filament\Resources\FixtureStats;

use App\Filament\Resources\FixtureStats\Pages\CreateFixtureStat;
use App\Filament\Resources\FixtureStats\Pages\EditFixtureStat;
use App\Filament\Resources\FixtureStats\Pages\ListFixtureStats;
use App\Filament\Resources\FixtureStats\Schemas\FixtureStatForm;
use App\Filament\Resources\FixtureStats\Tables\FixtureStatsTable;
use App\Models\FixtureStat;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class FixtureStatResource extends Resource
{
    protected static ?string $model = FixtureStat::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return FixtureStatForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FixtureStatsTable::configure($table);
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
            'index' => ListFixtureStats::route('/'),
            'create' => CreateFixtureStat::route('/create'),
            'edit' => EditFixtureStat::route('/{record}/edit'),
        ];
    }
}
