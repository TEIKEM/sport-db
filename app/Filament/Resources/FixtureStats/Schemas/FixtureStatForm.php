<?php

namespace App\Filament\Resources\FixtureStats\Schemas;

use App\Models\Fixture;
use App\Models\League;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Carbon;

class FixtureStatForm
{
    /** Mémoire pour éviter de recharger le même match plusieurs fois. */
    private static array $fixtures = [];

    public static function configure(Schema $schema): Schema
    {
        $components = [
            // Filtre 1 : Compétition
            Select::make('league_filter')
                ->label('Compétition')
                ->options(fn () => League::orderBy('name')->pluck('name', 'id')->all())
                ->searchable()
                ->live()
                ->dehydrated(false)
                ->hiddenOn('edit')
                ->afterStateUpdated(fn (Set $set) => $set('fixture_id', null))
                ->columnSpan(1),

            // Filtre 2 : Journée (Matchday) en champ séparé
            TextInput::make('matchday_filter')
                ->label('Journée (Matchday)')
                ->placeholder('Ex: 12')
                ->numeric()
                ->live()
                ->dehydrated(false)
                ->hiddenOn('edit')
                ->afterStateUpdated(fn (Set $set) => $set('fixture_id', null))
                ->columnSpan(1),

            // Sélection du Match
            Select::make('fixture_id')
                ->label('Match déjà joué')
                ->searchable()
                ->getSearchResultsUsing(function (string $search, Get $get): array {
                    return Fixture::query()
                        ->with(['league', 'homeTeam', 'awayTeam'])
                        ->where(function ($q) {
                            $q->where('status', 'FINISHED')
                              ->orWhere('kickoff_at', '<=', now());
                        })
                        ->when($get('league_filter'), fn ($q, $leagueId) => $q->where('league_id', $leagueId))
                        ->when($get('matchday_filter'), fn ($q, $matchday) => $q->where('matchday', $matchday))
                        ->when($search !== '', function ($query) use ($search) {
                            $query->where(function ($q) use ($search) {
                                $q->whereHas('homeTeam', fn ($t) => $t->where('name', 'like', "%{$search}%"))
                                  ->orWhereHas('awayTeam', fn ($t) => $t->where('name', 'like', "%{$search}%"));
                            });
                        })
                        ->orderByDesc('kickoff_at')
                        ->limit(50)
                        ->get()
                        ->mapWithKeys(fn ($f) => [$f->id => self::fixtureLabel($f)])
                        ->all();
                })
                ->getOptionLabelUsing(function ($value): ?string {
                    $fixture = Fixture::with(['league', 'homeTeam', 'awayTeam'])->find($value);

                    return $fixture ? self::fixtureLabel($fixture) : null;
                })
                ->default(fn () => request()->integer('fixture_id') ?: null)
                ->live()
                ->disabledOn('edit')
                ->helperText('Tape le nom d\'une équipe pour trouver le match.')
                ->required()
                ->columnSpanFull(),
        ];

        // Une ligne par type de stat, avec la case de chaque équipe
        foreach (self::stats() as $key => [$label, $type]) {
            $components[] = Fieldset::make($label)
                ->columns(2)
                ->schema([
                    self::input("home.{$key}", $type)
                        ->label(fn (Get $get): string => self::teamName($get('fixture_id'), 'home')),
                    self::input("away.{$key}", $type)
                        ->label(fn (Get $get): string => self::teamName($get('fixture_id'), 'away')),
                ])
                ->columnSpanFull();
        }

        return $schema->components($components)->columns(2);
    }

    /**
     * Les mêmes cases (une ligne par stat, Domicile et Extérieur),
     * pour un match déjà connu. Utilisé dans l'onglet Stats de la page d'un match.
     */
    public static function statFieldsets(Fixture $fixture): array
    {
        $home = $fixture->homeTeam->name;
        $away = $fixture->awayTeam->name;
        $fieldsets = [];

        foreach (self::stats() as $key => [$label, $type]) {
            $fieldsets[] = Fieldset::make($label)
                ->columns(2)
                ->schema([
                    self::input("home.{$key}", $type)->label("Domicile : {$home}"),
                    self::input("away.{$key}", $type)->label("Extérieur : {$away}"),
                ]);
        }

        return $fieldsets;
    }

    /** Les types de stats à saisir : clé => [libellé, type de valeur]. */
    private static function stats(): array
    {
        return [
            'possession' => ['Possession (%)', 'percent'],
            'shots' => ['Tirs', 'int'],
            'shots_on_target' => ['Tirs cadrés', 'int'],
            'corners' => ['Corners', 'int'],
            'fouls' => ['Fautes', 'int'],
            'yellow_cards' => ['Cartons jaunes', 'int'],
            'red_cards' => ['Cartons rouges', 'int'],
            'xg' => ['xG (buts attendus)', 'decimal'],
        ];
    }

    private static function input(string $name, string $type): TextInput
    {
        $field = TextInput::make($name);

        return match ($type) {
            'percent' => $field->integer()->minValue(0)->maxValue(100),
            'decimal' => $field->numeric()->minValue(0)->step(0.01),
            default => $field->integer()->minValue(0),
        };
    }

    private static function fixtureLabel(Fixture $fixture): string
    {
        $score = ($fixture->home_score !== null && $fixture->away_score !== null)
            ? ' ' . $fixture->home_score . '-' . $fixture->away_score . ' '
            : ' - ';

        return $fixture->league->name
            . ' · ' . Carbon::parse($fixture->kickoff_at)->format('d/m/Y')
            . ' · ' . $fixture->homeTeam->name . $score . $fixture->awayTeam->name;
    }

    private static function teamName($fixtureId, string $side): string
    {
        $default = $side === 'home' ? 'Domicile' : 'Extérieur';

        if (! $fixtureId) {
            return $default;
        }

        $fixture = self::$fixtures[$fixtureId] ??= Fixture::with(['homeTeam', 'awayTeam'])->find($fixtureId);
        $team = $side === 'home' ? $fixture?->homeTeam : $fixture?->awayTeam;

        return $team ? $default . ' : ' . $team->name : $default;
    }
}
