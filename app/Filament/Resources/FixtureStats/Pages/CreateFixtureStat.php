<?php

namespace App\Filament\Resources\FixtureStats\Pages;

use App\Filament\Resources\FixtureStats\FixtureStatResource;
use Filament\Resources\Pages\CreateRecord;
use App\Support\FixtureStatSaver;
use Illuminate\Database\Eloquent\Model;
class CreateFixtureStat extends CreateRecord
{
    protected static string $resource = FixtureStatResource::class;
    protected function handleRecordCreation(array $data): Model
{
    return FixtureStatSaver::save($data);
}
}
