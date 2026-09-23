<?php

namespace App\Filament\Resources\FixtureStats\Pages;

use App\Filament\Resources\FixtureStats\FixtureStatResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use App\Support\FixtureStatSaver;
use Illuminate\Database\Eloquent\Model;
class EditFixtureStat extends EditRecord
{
    protected static string $resource = FixtureStatResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
    protected function mutateFormDataBeforeFill(array $data): array
{
    return FixtureStatSaver::load((int) $data['fixture_id']);
}

protected function handleRecordUpdate(Model $record, array $data): Model
{
    $data['fixture_id'] = $record->fixture_id;

    return FixtureStatSaver::save($data);
}
}
