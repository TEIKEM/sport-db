<?php

namespace App\Filament\Resources\Fixtures\RelationManagers;

use App\Models\Fixture;
use App\Models\Player;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class EventsRelationManager extends RelationManager
{
    protected static string $relationship = 'events';

    protected static ?string $title = 'Événements';

    private const TYPES = [
        'GOAL' => 'But',
        'OWN_GOAL' => 'But contre son camp',
        'PENALTY_GOAL' => 'But sur penalty',
        'PENALTY_MISSED' => 'Penalty manqué',
        'YELLOW' => 'Carton jaune',
        'RED' => 'Carton rouge',
        'SUB' => 'Remplacement',
    ];

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('minute')
                ->label('Minute')
                ->integer()
                ->minValue(0)
                ->maxValue(130)
                ->required(),

            Select::make('type')
                ->label('Type d\'événement')
                ->options(self::TYPES)
                ->required(),

            Select::make('team_id')
                ->label('Équipe')
                ->options(fn (RelationManager $livewire): array => self::teamOptions($livewire->getOwnerRecord()))
                ->live()
                ->required()
                ->helperText('Pour un but : l\'équipe qui a marqué.'),

            Select::make('player_id')
                ->label('Joueur (facultatif)')
                ->options(fn (Get $get): array => $get('team_id')
                    ? Player::where('team_id', $get('team_id'))->orderBy('name')->pluck('name', 'id')->all()
                    : [])
                ->searchable()
                ->helperText('Choisis d\'abord l\'équipe. La liste vient du menu Players.'),

            TextInput::make('notes')
                ->label('Notes')
                ->maxLength(255),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('minute')
            ->columns([
                TextColumn::make('minute')->label('Minute')->sortable(),
                TextColumn::make('type')
                    ->label('Type')
                    ->formatStateUsing(fn ($state): string => self::TYPES[$state] ?? (string) $state),
                TextColumn::make('team.name')->label('Équipe'),
                TextColumn::make('player.name')->label('Joueur'),
                TextColumn::make('notes')->label('Notes'),
            ])
            ->headerActions([
                CreateAction::make()->label('Ajouter un événement'),
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
}
