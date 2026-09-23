<?php

namespace App\Filament\Resources\Fixtures\RelationManagers;

use App\Filament\Resources\FixtureStats\Schemas\FixtureStatForm;
use App\Support\FixtureStatSaver;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class StatsRelationManager extends RelationManager
{
    protected static string $relationship = 'stats';

    protected static ?string $title = 'Stats';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('team.name')->label('Équipe'),
                TextColumn::make('possession')->label('Possession %'),
                TextColumn::make('shots')->label('Tirs'),
                TextColumn::make('shots_on_target')->label('Tirs cadrés'),
                TextColumn::make('corners')->label('Corners'),
                TextColumn::make('fouls')->label('Fautes'),
                TextColumn::make('yellow_cards')->label('Jaunes'),
                TextColumn::make('red_cards')->label('Rouges'),
                TextColumn::make('xg')->label('xG'),
            ])
            ->headerActions([
                Action::make('saisir_stats')
                    ->label('Saisir / modifier les stats des deux équipes')
                    ->icon('heroicon-o-pencil-square')
                    ->modalHeading('Stats du match : Domicile et Extérieur')
                    ->modalSubmitActionLabel('Enregistrer')
                    ->modalWidth('3xl')
                    ->fillForm(fn (): array => FixtureStatSaver::load($this->getOwnerRecord()->id))
                    ->schema(fn (): array => FixtureStatForm::statFieldsets($this->getOwnerRecord()))
                    ->action(function (array $data): void {
                        $data['fixture_id'] = $this->getOwnerRecord()->id;

                        FixtureStatSaver::save($data);

                        Notification::make()
                            ->title('Stats enregistrées pour les deux équipes')
                            ->success()
                            ->send();
                    }),
            ])
            ->recordActions([
                DeleteAction::make(),
            ]);
    }
}
