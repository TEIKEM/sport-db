<?php

namespace App\Filament\Resources\Referees\Pages;

use App\Filament\Resources\Referees\RefereeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListReferees extends ListRecords
{
    protected static string $resource = RefereeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
