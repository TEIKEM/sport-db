<?php

namespace App\Filament\Resources\FixtureStats\Pages;

use App\Filament\Resources\FixtureStats\FixtureStatResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListFixtureStats extends ListRecords
{
    protected static string $resource = FixtureStatResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
