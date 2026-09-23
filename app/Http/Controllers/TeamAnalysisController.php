<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Support\TeamStatsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TeamAnalysisController extends Controller
{
    /**
     * Page de recherche : recap complet d'une équipe (global, par saison, par
     * compétition) et, si une 2e équipe est choisie, leur confrontation directe.
     */
    public function index(Request $request)
    {
        $team = $request->filled('team') ? Team::find($request->integer('team')) : null;
        $team2 = $request->filled('team2') ? Team::find($request->integer('team2')) : null;

        $seasons = collect();
        $leagues = collect();
        $global = null;
        $matches = collect();
        $bySeason = collect();
        $byCompetition = collect();
        $h2h = null;
        $h2hMatches = collect();
        $filters = ['league_id' => null, 'season_id' => null];

        if ($team) {
            $filters = [
                'league_id' => $request->integer('league_id') ?: null,
                'season_id' => $request->integer('season_id') ?: null,
            ];

            // Pour remplir les listes déroulantes : uniquement les saisons/compétitions où l'équipe a joué
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

            $allMatches = TeamStatsService::matches($team->id, $filters);
            $global = TeamStatsService::summary($allMatches);
            $matches = $allMatches->take(50);

            // Stats par saison (déjà calculées par la vue team_season_stats)
            $bySeason = DB::table('team_season_stats as s')
                ->join('leagues as l', 'l.id', '=', 's.league_id')
                ->join('seasons as se', 'se.id', '=', 's.season_id')
                ->where('s.team_id', $team->id)
                ->orderByDesc('se.start_date')
                ->select('s.*', 'l.name as league_name', 'se.label as season_label')
                ->get();

            // Stats par compétition, toutes saisons confondues
            $byCompetition = DB::table('team_matches as tm')
                ->join('leagues as l', 'l.id', '=', 'tm.league_id')
                ->where('tm.team_id', $team->id)
                ->select('tm.league_id', 'l.name as league_name')
                ->selectRaw('COUNT(*) as played')
                ->selectRaw("SUM(tm.result = 'W') as wins")
                ->selectRaw("SUM(tm.result = 'D') as draws")
                ->selectRaw("SUM(tm.result = 'L') as losses")
                ->selectRaw('SUM(tm.goals_for) as goals_for')
                ->selectRaw('SUM(tm.goals_against) as goals_against')
                ->groupBy('tm.league_id', 'l.name')
                ->orderByDesc('played')
                ->get();

            if ($team2) {
                $h2hFilters = array_filter([
                    'opponent_id' => $team2->id,
                    'league_id' => $filters['league_id'],
                    'season_id' => $filters['season_id'],
                ]);

                $h2hMatches = TeamStatsService::matches($team->id, $h2hFilters);
                $h2h = TeamStatsService::summary($h2hMatches);
            }
        }

        return view('analyse', compact(
            'team', 'team2', 'seasons', 'leagues', 'filters',
            'global', 'matches', 'bySeason', 'byCompetition', 'h2h', 'h2hMatches'
        ));
    }

    /**
     * Recherche d'équipes par nom, utilisée par la barre de recherche (autocomplétion).
     */
    public function search(Request $request)
    {
        $q = trim((string) $request->get('q', ''));

        $teams = Team::query()
            ->when($q !== '', fn ($query) => $query->where('name', 'like', "%{$q}%"))
            ->orderBy('name')
            ->limit(15)
            ->get(['id', 'name']);

        return response()->json($teams);
    }
}
