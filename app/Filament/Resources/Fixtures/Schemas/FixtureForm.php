<?php

namespace App\Filament\Resources\Fixtures\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Database\Eloquent\Builder;
class FixtureForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('league_id')
                ->relationship('league', 'name'),
                Select::make('season_id')
                ->relationship('season', 'label')
                ->live()
                ->required(),
                TextInput::make('source')
                    ->required()
                    ->default('manual'),
                TextInput::make('external_id')
                    ->numeric(),
                Select::make('home_team_id')
          ->relationship('homeTeam', 'name', modifyQueryUsing: fn (Builder $query, Get $get) => $get('season_id')
            ? $query->whereHas('seasons', fn ($q) => $q->where('seasons.id', $get('season_id')))
            : $query
    )
    ->searchable()
    ->preload()
    ->required(),Select::make('away_team_id')
    ->relationship(
        'awayTeam', 'name',
        modifyQueryUsing: fn (Builder $query, Get $get) => $get('season_id')
            ? $query->whereHas('seasons', fn ($q) => $q->where('seasons.id', $get('season_id')))
            : $query
    )
    ->searchable()
    ->preload()
    ->required(),
                DateTimePicker::make('kickoff_at'),
              Select::make('status')
    ->label('Statut')
    ->options([
        'SCHEDULED' => 'Programmé',
        'LIVE' => 'En cours',
        'FINISHED' => 'Terminé',
        'POSTPONED' => 'Reporté',
        'CANCELLED' => 'Annulé',
    ])
    ->default('SCHEDULED')
    ->required(),

Select::make('stage')
    ->label('Phase')
    ->options([
        'REGULAR_SEASON' => 'Saison régulière',
        'GROUP_STAGE' => 'Phase de groupes',
        'LEAGUE_PHASE' => 'Phase de ligue',
        'PLAYOFF' => 'Barrages',
        'ROUND_OF_32' => '16es de finale',
        'ROUND_OF_16' => '8es de finale',
        'QUARTER_FINAL' => 'Quarts de finale',
        'SEMI_FINAL' => 'Demi-finales',
        'THIRD_PLACE' => 'Match pour la 3e place',
        'FINAL' => 'Finale',
    ]),

TextInput::make('group_name')->label('Groupe (A, B, C...)')->maxLength(10),
Toggle::make('is_neutral')->label('Terrain neutre'),

Select::make('decided_by')
    ->label('Décidé par')
    ->options([
        'REGULAR' => '90 minutes',
        'EXTRA_TIME' => 'Prolongations',
        'PENALTIES' => 'Tirs au but',
    ]),

TextInput::make('et_home_score')->label('Score dom. après prolongations')->numeric(),
TextInput::make('et_away_score')->label('Score ext. après prolongations')->numeric(),
TextInput::make('pen_home_score')->label('Tirs au but dom.')->numeric(),
TextInput::make('pen_away_score')->label('Tirs au but ext.')->numeric(),
                TextInput::make('home_score')
                    ->numeric(),
                TextInput::make('away_score')
                    ->numeric(),
                TextInput::make('ht_home_score')
                    ->numeric(),
                TextInput::make('ht_away_score')
                    ->numeric(),
                Select::make('referee_id')->relationship('referee', 'name'),
                TextInput::make('venue'),
                TextInput::make('matchday')
                    ->numeric(),
                TextInput::make('weather'),
                TextInput::make('attendance')
                    ->numeric(),
                Textarea::make('notes')
                    ->columnSpanFull(),
            ]);
    }
}
