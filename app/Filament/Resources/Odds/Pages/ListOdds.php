<?php

namespace App\Filament\Resources\Odds\Pages;

use App\Filament\Resources\Odds\OddResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListOdds extends ListRecords
{
    protected static string $resource = OddResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
