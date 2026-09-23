<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Support\TeamStatsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PronosticsController extends Controller
{
    /**
     * Recherche d'une équipe (et, en option, une 2e équipe) et affiche toutes
     * les statistiques disponibles en pourcentages, côte à côte si deux
     * équipes sont choisies.
     */
    public function index(Request $request)
    {
        $team = $request->filled('team') ? Team::find($request->integer('team')) : null;
        $team2 = $request->filled('team2') ? Team::find($request->integer('team2')) : null;

        $seasons = collect();
        $leagues = collect();
        $filters = ['league_id' => null, 'season_id' => null];
        $detail1 = null;
        $detail2 = null;
        $avg1 = null;
        $avg2 = null;

        if ($team) {
            $filters = [
                'league_id' => $request->integer('league_id') ?: null,
                'season_id' => $request->integer('season_id') ?: null,
            ];

            $seasons = DB::table('team_matches as tm')
                ->join('seasons as se', 'se.id', '=', 'tm.season_id')
                ->join('leagues as l', 'l.id', '=', 'tm.league_id')
                ->where('tm.team_id', $team->id)
                ->distinct()
                ->orderByDesc('se.start_date')
                ->get(['se.id', 'se.label', 'l.name as league_name', 'se.start_date']);

            $leagues = DB::table('team_matches as tm')
                ->join('leagues as l', 'l.id', '=', 'tm.league_id')
                ->where('tm.team_id', $team->id)
                ->distinct()
                ->orderBy('l.name')
                ->get(['l.id', 'l.name']);

            $matches1 = TeamStatsService::matches($team->id, $filters);
            $detail1 = TeamStatsService::detailed($matches1);
            $avg1 = TeamStatsService::matchStatAverages($team->id, $filters);

            if ($team2) {
                $matches2 = TeamStatsService::matches($team2->id, $filters);
                $detail2 = TeamStatsService::detailed($matches2);
                $avg2 = TeamStatsService::matchStatAverages($team2->id, $filters);
            }
        }

        return view('pronostics', compact(
            'team', 'team2', 'seasons', 'leagues', 'filters',
            'detail1', 'detail2', 'avg1', 'avg2'
        ));
    }
}
