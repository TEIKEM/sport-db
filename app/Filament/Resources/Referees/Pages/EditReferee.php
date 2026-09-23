<?php

namespace App\Filament\Resources\Referees\Pages;

use App\Filament\Resources\Referees\RefereeResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditReferee extends EditRecord
{
    protected static string $resource = RefereeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
