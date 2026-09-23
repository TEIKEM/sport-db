<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Calcule les statistiques d'une équipe à partir de la vue team_matches,
 * avec des filtres optionnels : adversaire précis (confrontations directes),
 * compétition, saison. Utilisé par les pages Analyse d'équipe et Pronostics.
 */
class TeamStatsService
{
    /**
     * Renvoie les matchs d'une équipe (les plus récents d'abord), avec filtres optionnels.
     *
     * @param  array{opponent_id?: int|null, league_id?: int|null, season_id?: int|null}  $filters
     */
    public static function matches(int $teamId, array $filters = []): Collection
    {
        return DB::table('team_matches as tm')
            ->join('teams as o', 'o.id', '=', 'tm.opponent_id')
            ->join('leagues as l', 'l.id', '=', 'tm.league_id')
            ->join('seasons as se', 'se.id', '=', 'tm.season_id')
            ->where('tm.team_id', $teamId)
            ->when($filters['opponent_id'] ?? null, fn ($q, $v) => $q->where('tm.opponent_id', $v))
            ->when($filters['league_id'] ?? null, fn ($q, $v) => $q->where('tm.league_id', $v))
            ->when($filters['season_id'] ?? null, fn ($q, $v) => $q->where('tm.season_id', $v))
            ->orderByDesc('tm.kickoff_at')
            ->select('tm.*', 'o.name as opponent_name', 'l.name as league_name', 'se.label as season_label')
            ->get();
    }

    /**
     * Résume une collection de matchs (issue de matches()) : V/N/D, moyennes, over/under, BTTS, forme.
     * Renvoie ['n' => 0] si la collection est vide.
     */
    public static function summary(Collection $matches): array
    {
        $n = $matches->count();

        if ($n === 0) {
            return ['n' => 0, 'form' => []];
        }

        $pct = fn (callable $test) => (int) round(100 * $matches->filter($test)->count() / $n);
        $avg = fn (string $col) => $matches->whereNotNull($col)->count()
            ? round((float) $matches->avg($col), 2)
            : null;

        return [
            'n' => $n,
            'wins' => $matches->where('result', 'W')->count(),
            'draws' => $matches->where('result', 'D')->count(),
            'losses' => $matches->where('result', 'L')->count(),
            'points' => (int) $matches->sum('points'),
            'goals_for' => (int) $matches->sum('goals_for'),
            'goals_against' => (int) $matches->sum('goals_against'),
            'avg_for' => $avg('goals_for'),
            'avg_against' => $avg('goals_against'),
            'avg_h2_for' => $avg('h2_for'),
            'avg_h2_against' => $avg('h2_against'),
            'over_0_5' => $pct(fn ($m) => $m->total_goals > 0.5),
            'over_1_5' => $pct(fn ($m) => $m->total_goals > 1.5),
            'over_2_5' => $pct(fn ($m) => $m->total_goals > 2.5),
            'over_3_5' => $pct(fn ($m) => $m->total_goals > 3.5),
            'btts' => $pct(fn ($m) => $m->btts == 1),
            'clean_sheets' => $pct(fn ($m) => $m->goals_against == 0),
            'failed_to_score' => $pct(fn ($m) => $m->goals_for == 0),
            'form' => $matches->take(5)->pluck('result')->all(),
        ];
    }

    /**
     * Détail complet, par catégorie, de tous les pourcentages/moyennes possibles
     * à partir d'une collection de matchs (issue de matches()). Structure stable
     * (mêmes catégories et libellés) même quand la collection est vide, pour
     * pouvoir afficher deux équipes côte à côte ligne par ligne.
     */
    public static function detailed(Collection $matches): array
    {
        $n = $matches->count();

        $withHt = $matches->filter(fn ($m) => $m->ht_for !== null && $m->ht_against !== null);
        $nHt = $withHt->count();

        $home = $matches->where('is_home', 1);
        $nHome = $home->count();
        $away = $matches->where('is_home', 0);
        $nAway = $away->count();

        $pct = function (Collection $set, callable $test, ?int $total = null) {
            $total ??= $set->count();

            return $total ? round(100 * $set->filter($test)->count() / $total) . ' %' : '–';
        };

        $avg = function (Collection $set, string $col) {
            $set = $set->whereNotNull($col);

            return $set->count() ? (string) round((float) $set->avg($col), 2) : '–';
        };

        return [
            'n' => $n,
            'categories' => [
                [
                    'title' => 'Résumé',
                    'rows' => [
                        ['label' => 'Matchs analysés', 'value' => (string) $n],
                        ['label' => 'Victoires', 'value' => $pct($matches, fn ($m) => $m->result === 'W', $n)],
                        ['label' => 'Nuls', 'value' => $pct($matches, fn ($m) => $m->result === 'D', $n)],
                        ['label' => 'Défaites', 'value' => $pct($matches, fn ($m) => $m->result === 'L', $n)],
                        ['label' => 'Double chance : Victoire ou nul (1X)', 'value' => $pct($matches, fn ($m) => $m->result !== 'L', $n)],
                        ['label' => 'Double chance : Nul ou défaite (X2)', 'value' => $pct($matches, fn ($m) => $m->result !== 'W', $n)],
                        ['label' => 'Double chance : Victoire ou défaite (12)', 'value' => $pct($matches, fn ($m) => $m->result !== 'D', $n)],
                    ],
                ],
                [
                    'title' => 'Buts (moyennes par match)',
                    'rows' => [
                        ['label' => 'Buts marqués', 'value' => $avg($matches, 'goals_for')],
                        ['label' => 'Buts encaissés', 'value' => $avg($matches, 'goals_against')],
                        ['label' => 'Total buts du match', 'value' => $avg($matches, 'total_goals')],
                    ],
                ],
                [
                    'title' => 'Over / Under — total du match',
                    'rows' => [
                        ['label' => 'Plus de 0.5 but', 'value' => $pct($matches, fn ($m) => $m->total_goals > 0.5, $n)],
                        ['label' => 'Plus de 1.5 but', 'value' => $pct($matches, fn ($m) => $m->total_goals > 1.5, $n)],
                        ['label' => 'Plus de 2.5 buts', 'value' => $pct($matches, fn ($m) => $m->total_goals > 2.5, $n)],
                        ['label' => 'Plus de 3.5 buts', 'value' => $pct($matches, fn ($m) => $m->total_goals > 3.5, $n)],
                        ['label' => 'Plus de 4.5 buts', 'value' => $pct($matches, fn ($m) => $m->total_goals > 4.5, $n)],
                        ['label' => 'Moins de 1.5 but', 'value' => $pct($matches, fn ($m) => $m->total_goals < 1.5, $n)],
                        ['label' => 'Moins de 2.5 buts', 'value' => $pct($matches, fn ($m) => $m->total_goals < 2.5, $n)],
                        ['label' => 'Moins de 3.5 buts', 'value' => $pct($matches, fn ($m) => $m->total_goals < 3.5, $n)],
                        ['label' => 'Les deux équipes marquent (BTTS oui)', 'value' => $pct($matches, fn ($m) => $m->btts == 1, $n)],
                        ['label' => 'Les deux équipes ne marquent pas toutes les deux (BTTS non)', 'value' => $pct($matches, fn ($m) => $m->btts == 0, $n)],
                    ],
                ],
                [
                    'title' => 'Buts de l\'équipe elle-même',
                    'rows' => [
                        ['label' => 'Marque plus de 0.5 but', 'value' => $pct($matches, fn ($m) => $m->goals_for > 0.5, $n)],
                        ['label' => 'Marque plus de 1.5 but', 'value' => $pct($matches, fn ($m) => $m->goals_for > 1.5, $n)],
                        ['label' => 'Marque plus de 2.5 buts', 'value' => $pct($matches, fn ($m) => $m->goals_for > 2.5, $n)],
                        ['label' => 'Encaisse plus de 0.5 but', 'value' => $pct($matches, fn ($m) => $m->goals_against > 0.5, $n)],
                        ['label' => 'Encaisse plus de 1.5 but', 'value' => $pct($matches, fn ($m) => $m->goals_against > 1.5, $n)],
                        ['label' => 'Encaisse plus de 2.5 buts', 'value' => $pct($matches, fn ($m) => $m->goals_against > 2.5, $n)],
                    ],
                ],
                [
                    'title' => 'Défense / efficacité',
                    'rows' => [
                        ['label' => 'Clean sheet (n\'encaisse pas)', 'value' => $pct($matches, fn ($m) => $m->goals_against == 0, $n)],
                        ['label' => 'Ne marque pas', 'value' => $pct($matches, fn ($m) => $m->goals_for == 0, $n)],
                        ['label' => 'Gagne sans encaisser', 'value' => $pct($matches, fn ($m) => $m->result === 'W' && $m->goals_against == 0, $n)],
                        ['label' => 'Perd sans avoir marqué', 'value' => $pct($matches, fn ($m) => $m->result === 'L' && $m->goals_for == 0, $n)],
                    ],
                ],
                [
                    'title' => '1re mi-temps' . ($nHt < $n ? " (sur {$nHt} match(s) avec score de mi-temps saisi)" : ''),
                    'rows' => [
                        ['label' => 'Plus de 0.5 but en 1re MT (total)', 'value' => $pct($withHt, fn ($m) => ($m->ht_for + $m->ht_against) > 0.5, $nHt)],
                        ['label' => 'Plus de 1.5 but en 1re MT (total)', 'value' => $pct($withHt, fn ($m) => ($m->ht_for + $m->ht_against) > 1.5, $nHt)],
                        ['label' => 'L\'équipe marque en 1re MT', 'value' => $pct($withHt, fn ($m) => $m->ht_for > 0, $nHt)],
                        ['label' => 'L\'équipe encaisse en 1re MT', 'value' => $pct($withHt, fn ($m) => $m->ht_against > 0, $nHt)],
                        ['label' => 'Mène à la mi-temps', 'value' => $pct($withHt, fn ($m) => $m->ht_for > $m->ht_against, $nHt)],
                        ['label' => 'Égalité à la mi-temps', 'value' => $pct($withHt, fn ($m) => $m->ht_for == $m->ht_against, $nHt)],
                        ['label' => 'Mené à la mi-temps', 'value' => $pct($withHt, fn ($m) => $m->ht_for < $m->ht_against, $nHt)],
                    ],
                ],
                [
                    'title' => '2e mi-temps' . ($nHt < $n ? " (sur {$nHt} match(s) avec score de mi-temps saisi)" : ''),
                    'rows' => [
                        ['label' => 'Plus de 0.5 but en 2e MT (total)', 'value' => $pct($withHt, fn ($m) => ($m->h2_for + $m->h2_against) > 0.5, $nHt)],
                        ['label' => 'Plus de 1.5 but en 2e MT (total)', 'value' => $pct($withHt, fn ($m) => ($m->h2_for + $m->h2_against) > 1.5, $nHt)],
                        ['label' => 'L\'équipe marque en 2e MT', 'value' => $pct($withHt, fn ($m) => $m->h2_for > 0, $nHt)],
                        ['label' => 'L\'équipe encaisse en 2e MT', 'value' => $pct($withHt, fn ($m) => $m->h2_against > 0, $nHt)],
                        ['label' => 'Remontée (mené à la MT puis gagne)', 'value' => $pct($withHt, fn ($m) => $m->ht_for < $m->ht_against && $m->result === 'W', $nHt)],
                        ['label' => 'Perd son avantage (mène à la MT puis pas de victoire)', 'value' => $pct($withHt, fn ($m) => $m->ht_for > $m->ht_against && $m->result !== 'W', $nHt)],
                        ['label' => 'Buts encaissés en 2e MT (moyenne)', 'value' => $avg($withHt, 'h2_against')],
                        ['label' => 'Buts marqués en 2e MT (moyenne)', 'value' => $avg($withHt, 'h2_for')],
                    ],
                ],
                [
                    'title' => 'Domicile / Extérieur',
                    'rows' => [
                        ['label' => "Victoires à domicile ({$nHome} match(s))", 'value' => $pct($home, fn ($m) => $m->result === 'W', $nHome)],
                        ['label' => "Victoires à l'extérieur ({$nAway} match(s))", 'value' => $pct($away, fn ($m) => $m->result === 'W', $nAway)],
                        ['label' => 'Plus de 2.5 buts à domicile', 'value' => $pct($home, fn ($m) => $m->total_goals > 2.5, $nHome)],
                        ['label' => 'Plus de 2.5 buts à l\'extérieur', 'value' => $pct($away, fn ($m) => $m->total_goals > 2.5, $nAway)],
                        ['label' => 'BTTS à domicile', 'value' => $pct($home, fn ($m) => $m->btts == 1, $nHome)],
                        ['label' => 'BTTS à l\'extérieur', 'value' => $pct($away, fn ($m) => $m->btts == 1, $nAway)],
                    ],
                ],
            ],
        ];
    }

    /**
     * Moyennes des stats de match saisies à la main (possession, tirs, corners...),
     * pour une équipe, avec les mêmes filtres optionnels que matches(). Renvoie
     * null si aucune stat n'a été saisie pour la sélection.
     *
     * @param  array{league_id?: int|null, season_id?: int|null}  $filters
     */
    public static function matchStatAverages(int $teamId, array $filters = []): ?object
    {
        $row = DB::table('fixture_stats as fs')
            ->join('fixtures as f', 'f.id', '=', 'fs.fixture_id')
            ->where('fs.team_id', $teamId)
            ->where('f.status', 'FINISHED')
            ->when($filters['league_id'] ?? null, fn ($q, $v) => $q->where('f.league_id', $v))
            ->when($filters['season_id'] ?? null, fn ($q, $v) => $q->where('f.season_id', $v))
            ->selectRaw('COUNT(*) as n')
            ->selectRaw('ROUND(AVG(fs.possession), 1) as possession')
            ->selectRaw('ROUND(AVG(fs.shots), 1) as shots')
            ->selectRaw('ROUND(AVG(fs.shots_on_target), 1) as shots_on_target')
            ->selectRaw('ROUND(AVG(fs.corners), 1) as corners')
            ->selectRaw('ROUND(AVG(fs.fouls), 1) as fouls')
            ->selectRaw('ROUND(AVG(fs.yellow_cards), 2) as yellow_cards')
            ->selectRaw('ROUND(AVG(fs.red_cards), 2) as red_cards')
            ->selectRaw('ROUND(AVG(fs.xg), 2) as xg')
            ->first();

        return ($row && $row->n > 0) ? $row : null;
    }
}
