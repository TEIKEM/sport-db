<?php

namespace App\Filament\Resources\Fixtures\RelationManagers;

use App\Models\Fixture;
use App\Models\Player;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PlayerStatsRelationManager extends RelationManager
{
    protected static string $relationship = 'playerStats';

    protected static ?string $title = 'Stats joueurs';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('player_id')
                ->label('Joueur')
                ->options(fn (RelationManager $livewire): array => self::playerOptions($livewire->getOwnerRecord()))
                ->searchable()
                ->required()
                ->columnSpanFull()
                ->helperText('Joueur absent de la liste ? Utilise le petit + pour le créer.')
                ->createOptionForm([
                    TextInput::make('name')->label('Nom du joueur')->required(),
                    Select::make('team_id')
                        ->label('Équipe')
                        ->options(fn (RelationManager $livewire): array => self::teamOptions($livewire->getOwnerRecord()))
                        ->required(),
                    TextInput::make('position')->label('Poste')->maxLength(20),
                ])
                ->createOptionUsing(fn (array $data): int => Player::create($data)->getKey()),

            Toggle::make('is_starter')->label('Titulaire'),
            TextInput::make('minutes')->label('Minutes jouées')->integer()->minValue(0)->maxValue(130),
            TextInput::make('goals')->label('Buts')->integer()->minValue(0),
            TextInput::make('assists')->label('Passes décisives')->integer()->minValue(0),
            TextInput::make('shots')->label('Tirs')->integer()->minValue(0),
            TextInput::make('shots_on_target')->label('Tirs cadrés')->integer()->minValue(0),
            TextInput::make('yellow_cards')->label('Cartons jaunes')->integer()->minValue(0),
            TextInput::make('red_cards')->label('Cartons rouges')->integer()->minValue(0),
            TextInput::make('rating')->label('Note (sur 10)')->numeric()->minValue(0)->maxValue(10)->step(0.1),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('player.name')->label('Joueur')->searchable(),
                TextColumn::make('player.team.name')->label('Équipe'),
                IconColumn::make('is_starter')->label('Titulaire')->boolean(),
                TextColumn::make('minutes')->label('Min.'),
                TextColumn::make('goals')->label('Buts'),
                TextColumn::make('assists')->label('Passes D.'),
                TextColumn::make('shots')->label('Tirs'),
                TextColumn::make('shots_on_target')->label('Cadrés'),
                TextColumn::make('yellow_cards')->label('Jaunes'),
                TextColumn::make('red_cards')->label('Rouges'),
                TextColumn::make('rating')->label('Note'),
            ])
            ->headerActions([
                CreateAction::make()->label('Ajouter les stats d\'un joueur'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    private static function teamOptions(Fixture $fixture): array
    {
        return [
            $fixture->home_team_id => 'Domicile : ' . $fixture->homeTeam->name,
            $fixture->away_team_id => 'Extérieur : ' . $fixture->awayTeam->name,
        ];
    }

    private static function playerOptions(Fixture $fixture): array
    {
        return Player::query()
            ->with('team')
            ->whereIn('team_id', [$fixture->home_team_id, $fixture->away_team_id])
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn ($player) => [$player->id => $player->name . ' (' . $player->team->name . ')'])
            ->all();
    }
}
