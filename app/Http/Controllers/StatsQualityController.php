<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

class StatsQualityController extends Controller
{
    public function index()
    {
        $coverage = $this->coverage();
        $anomalies = $this->fieldAnomalies();
        $possessionIssues = $this->possessionIssues();

        return view('stats-quality', compact('coverage', 'anomalies', 'possessionIssues'));
    }

    /**
     * Pour chaque compétition : nombre de matchs terminés, combien ont les stats
     * des DEUX équipes (complet), d'une seule (partiel), ou d'aucune.
     */
    private function coverage()
    {
        return DB::table('fixtures as f')
            ->join('leagues as l', 'l.id', '=', 'f.league_id')
            ->leftJoin(DB::raw('(select fixture_id, count(*) as n from fixture_stats group by fixture_id) as s'), 's.fixture_id', '=', 'f.id')
            ->where('f.status', 'FINISHED')
            ->groupBy('l.id', 'l.name')
            ->selectRaw('l.id as league_id, l.name as league_name')
            ->selectRaw('COUNT(*) as finished')
            ->selectRaw('SUM(CASE WHEN s.n >= 2 THEN 1 ELSE 0 END) as complete')
            ->selectRaw('SUM(CASE WHEN s.n = 1 THEN 1 ELSE 0 END) as partial')
            ->selectRaw('SUM(CASE WHEN s.n IS NULL THEN 1 ELSE 0 END) as empty')
            ->orderByDesc('finished')
            ->get()
            ->map(function ($row) {
                $row->percent = $row->finished ? round(100 * $row->complete / $row->finished) : 0;

                return $row;
            });
    }

    /**
     * Valeurs impossibles ou très suspectes sur une seule ligne de stats.
     */
    private function fieldAnomalies()
    {
        return DB::table('fixture_stats as fs')
            ->join('fixtures as f', 'f.id', '=', 'fs.fixture_id')
            ->join('leagues as l', 'l.id', '=', 'f.league_id')
            ->join('teams as t', 't.id', '=', 'fs.team_id')
            ->selectRaw("
                fs.id as stat_id, f.id as fixture_id, f.kickoff_at, l.name as league_name, t.name as team_name,
                fs.possession, fs.shots, fs.shots_on_target, fs.corners, fs.fouls,
                fs.yellow_cards, fs.red_cards, fs.xg,
                CASE
                    WHEN fs.shots IS NOT NULL AND fs.shots_on_target IS NOT NULL AND fs.shots_on_target > fs.shots
                        THEN 'Tirs cadrés supérieurs aux tirs totaux'
                    WHEN fs.possession IS NOT NULL AND (fs.possession < 0 OR fs.possession > 100)
                        THEN 'Possession en dehors de 0-100 %'
                    WHEN fs.yellow_cards IS NOT NULL AND fs.yellow_cards > 11
                        THEN 'Plus de 11 cartons jaunes (une équipe a 11 joueurs)'
                    WHEN fs.red_cards IS NOT NULL AND fs.red_cards > 5
                        THEN 'Plus de 5 cartons rouges'
                    WHEN fs.fouls IS NOT NULL AND fs.fouls > 40
                        THEN 'Plus de 40 fautes, valeur très inhabituelle'
                    WHEN fs.corners IS NOT NULL AND fs.corners > 25
                        THEN 'Plus de 25 corners, valeur très inhabituelle'
                    WHEN fs.xg IS NOT NULL AND (fs.xg < 0 OR fs.xg > 10)
                        THEN 'xG en dehors de 0-10, valeur très inhabituelle'
                    ELSE NULL
                END as anomaly
            ")
            ->havingRaw('anomaly IS NOT NULL')
            ->orderByDesc('f.kickoff_at')
            ->get();
    }

    /**
     * Possession des deux équipes d'un même match qui ne totalise pas ~100 %
     * (tolérance de 5 points, pour les arrondis).
     */
    private function possessionIssues()
    {
        return DB::table('fixtures as f')
            ->join('leagues as l', 'l.id', '=', 'f.league_id')
            ->join('teams as ht', 'ht.id', '=', 'f.home_team_id')
            ->join('teams as at', 'at.id', '=', 'f.away_team_id')
            ->join('fixture_stats as h', function ($j) {
                $j->on('h.fixture_id', '=', 'f.id')->on('h.team_id', '=', 'f.home_team_id');
            })
            ->join('fixture_stats as a', function ($j) {
                $j->on('a.fixture_id', '=', 'f.id')->on('a.team_id', '=', 'f.away_team_id');
            })
            ->whereNotNull('h.possession')
            ->whereNotNull('a.possession')
            ->whereRaw('ABS((h.possession + a.possession) - 100) > 5')
            ->select([
                'f.id as fixture_id', 'f.kickoff_at', 'l.name as league_name',
                'ht.name as home_name', 'at.name as away_name',
                'h.possession as home_possession', 'a.possession as away_possession',
            ])
            ->orderByDesc('f.kickoff_at')
            ->get();
    }
}
