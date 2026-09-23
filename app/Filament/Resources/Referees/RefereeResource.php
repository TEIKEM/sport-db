<?php

namespace App\Filament\Resources\Referees;

use App\Filament\Resources\Referees\Pages\CreateReferee;
use App\Filament\Resources\Referees\Pages\EditReferee;
use App\Filament\Resources\Referees\Pages\ListReferees;
use App\Filament\Resources\Referees\Schemas\RefereeForm;
use App\Filament\Resources\Referees\Tables\RefereesTable;
use App\Models\Referee;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class RefereeResource extends Resource
{
    protected static ?string $model = Referee::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return RefereeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RefereesTable::configure($table);
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
            'index' => ListReferees::route('/'),
            'create' => CreateReferee::route('/create'),
            'edit' => EditReferee::route('/{record}/edit'),
        ];
    }
}
