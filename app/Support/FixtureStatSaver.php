<?php

namespace App\Support;

use App\Models\Fixture;
use App\Models\FixtureStat;

class FixtureStatSaver
{
    public const FIELDS = [
        'possession', 'shots', 'shots_on_target', 'corners',
        'fouls', 'yellow_cards', 'red_cards', 'xg',
    ];

    /**
     * Enregistre les stats des deux équipes du match et renvoie la ligne de l'équipe à domicile.
     */
    public static function save(array $data): FixtureStat
    {
        $fixture = Fixture::findOrFail($data['fixture_id']);
        $home = null;

        $sides = [
            'home' => $fixture->home_team_id,
            'away' => $fixture->away_team_id,
        ];

        foreach ($sides as $side => $teamId) {
            $values = [];

            foreach (self::FIELDS as $field) {
                $value = $data[$side][$field] ?? null;
                $values[$field] = ($value === '' ? null : $value);
            }

            $stat = FixtureStat::updateOrCreate(
                ['fixture_id' => $fixture->id, 'team_id' => $teamId],
                $values
            );

            if ($side === 'home') {
                $home = $stat;
            }
        }

        return $home;
    }

    /**
     * Prépare les données du formulaire à partir des deux lignes existantes.
     */
    public static function load(int $fixtureId): array
    {
        $fixture = Fixture::find($fixtureId);

        if (! $fixture) {
            return ['fixture_id' => $fixtureId, 'home' => [], 'away' => []];
        }

        $stats = FixtureStat::where('fixture_id', $fixtureId)->get()->keyBy('team_id');

        $extract = function ($stat): array {
            return $stat ? $stat->only(self::FIELDS) : [];
        };

        return [
            'fixture_id' => $fixtureId,
            'home' => $extract($stats->get($fixture->home_team_id)),
            'away' => $extract($stats->get($fixture->away_team_id)),
        ];
    }
}
