<?php

namespace App\Filament\Resources\Fixtures;

use App\Filament\Resources\Fixtures\Pages\CreateFixture;
use App\Filament\Resources\Fixtures\Pages\EditFixture;
use App\Filament\Resources\Fixtures\Pages\ListFixtures;
use App\Filament\Resources\Fixtures\Schemas\FixtureForm;
use App\Filament\Resources\Fixtures\Tables\FixturesTable;
use App\Models\Fixture;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class FixtureResource extends Resource
{
    protected static ?string $model = Fixture::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return FixtureForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FixturesTable::configure($table);
    }

    public static function getRelations(): array
{
    return [
        RelationManagers\StatsRelationManager::class,
        RelationManagers\EventsRelationManager::class,
        RelationManagers\PlayerStatsRelationManager::class,
        RelationManagers\OddsRelationManager::class,
    ];
}

    public static function getPages(): array
    {
        return [
            'index' => ListFixtures::route('/'),
            'create' => CreateFixture::route('/create'),
            'edit' => EditFixture::route('/{record}/edit'),
        ];
    }
}
