<?php

namespace App\Filament\Resources\Odds\Pages;

use App\Filament\Resources\Odds\OddResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditOdd extends EditRecord
{
    protected static string $resource = OddResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
