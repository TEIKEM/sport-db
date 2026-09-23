<?php

namespace App\Http\Controllers;

use App\Models\Fixture;
use App\Models\League;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnalyseController extends Controller
{
    /**
     * Page d'accueil : liste des matchs avec filtres + liste des compétitions.
     */
    public function home(Request $request)
    {
        $leagues = League::orderBy('name')->get();

        $fixtures = Fixture::with(['league', 'season', 'homeTeam', 'awayTeam'])
            ->when($request->filled('league'), fn ($q) => $q->where('league_id', $request->league))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%' . $request->q . '%';
                $q->where(function ($q) use ($term) {
                    $q->whereHas('homeTeam', fn ($t) => $t->where('name', 'like', $term))
                      ->orWhereHas('awayTeam', fn ($t) => $t->where('name', 'like', $term));
                });
            })
            ->orderByDesc('kickoff_at')
            ->paginate(30)
            ->withQueryString();

        $totals = [
            'fixtures' => Fixture::count(),
            'finished' => Fixture::where('status', 'FINISHED')->count(),
            'teams' => Team::count(),
            'leagues' => $leagues->count(),
        ];

        return view('home', compact('leagues', 'fixtures', 'totals'));
    }

    /**
     * Page d'une compétition : classement(s), tendances, derniers matchs.
     */
    public function competition(League $league, Request $request)
    {
        $seasons = $league->seasons()->orderByDesc('start_date')->orderByDesc('id')->get();

        $season = $request->filled('season')
            ? $seasons->firstWhere('id', (int) $request->season)
            : $seasons->first();

        $standings = collect();
        $tops = [];
        $fixtures = collect();

        if ($season) {
            $standings = DB::table('standings')
                ->where('league_id', $league->id)
                ->where('season_id', $season->id)
                ->orderBy('group_name')
                ->orderBy('pos')
                ->get()
                ->groupBy('group_name');

            $rows = DB::table('team_season_stats as s')
                ->join('teams as t', 't.id', '=', 's.team_id')
                ->where('s.league_id', $league->id)
                ->where('s.season_id', $season->id)
                ->where('s.played', '>=', 3)
                ->select('s.*', 't.name as team_name')
                ->get();

            $make = fn ($items) => $items->sortByDesc('value')->take(5)->values();
            $item = fn ($r, $value) => (object) ['id' => $r->team_id, 'name' => $r->team_name, 'value' => $value];

            $tops = [
                'Over 2.5 (% des matchs)' => $make($rows->map(fn ($r) => $item($r, round(100 * $r->over_2_5 / $r->played)))),
                'BTTS - les deux marquent (% des matchs)' => $make($rows->map(fn ($r) => $item($r, round(100 * $r->btts / $r->played)))),
                'Buts encaissés en 2e mi-temps (moyenne)' => $make(
                    $rows->filter(fn ($r) => $r->avg_h2_against !== null)
                        ->map(fn ($r) => $item($r, (float) $r->avg_h2_against))
                ),
                'Buts marqués par match (moyenne)' => $make($rows->map(fn ($r) => $item($r, (float) $r->avg_goals_for))),
            ];

            $fixtures = Fixture::with(['homeTeam', 'awayTeam'])
                ->where('league_id', $league->id)
                ->where('season_id', $season->id)
                ->orderByDesc('kickoff_at')
                ->limit(40)
                ->get();
        }

        return view('competition', compact('league', 'seasons', 'season', 'standings', 'tops', 'fixtures'));
    }

    /**
     * Page d'un match : résultat, stats, événements, cotes, analyse avant-match, face-à-face.
     */
    public function fixture(Fixture $fixture)
    {
        $fixture->load([
            'league', 'season', 'homeTeam', 'awayTeam', 'referee',
            'stats', 'events.team', 'events.player', 'odds', 'trades',
        ]);

        $stats = $fixture->stats->keyBy('team_id');
        $events = $fixture->events->sortBy('minute')->values();

        $oddsGroups = $fixture->odds
            ->sortBy('recorded_at')
            ->groupBy(fn ($o) => $o->bookmaker . ' | ' . $o->market . ' | ' . $o->selection);

        // Analyse avant-match : uniquement les matchs joués AVANT ce match
        $before = $fixture->kickoff_at;
        $home = $this->snapshot($fixture->home_team_id, $before);
        $away = $this->snapshot($fixture->away_team_id, $before);
        $homeVenue = $this->snapshot($fixture->home_team_id, $before, 1, 5);
        $awayVenue = $this->snapshot($fixture->away_team_id, $before, 0, 5);

        $expected = null;
        if ($home['avg_for'] !== null && $home['avg_against'] !== null
            && $away['avg_for'] !== null && $away['avg_against'] !== null) {
            $expected = round(
                ($home['avg_for'] + $away['avg_against']) / 2
                + ($away['avg_for'] + $home['avg_against']) / 2,
                2
            );
        }

        // Face-à-face
        $h = $fixture->home_team_id;
        $a = $fixture->away_team_id;

        $h2h = Fixture::with(['homeTeam', 'awayTeam', 'league'])
            ->where('status', 'FINISHED')
            ->whereNotNull('home_score')
            ->whereNotNull('away_score')
            ->where('id', '<>', $fixture->id)
            ->where(function ($q) use ($h, $a) {
                $q->where(fn ($x) => $x->where('home_team_id', $h)->where('away_team_id', $a))
                  ->orWhere(fn ($x) => $x->where('home_team_id', $a)->where('away_team_id', $h));
            })
            ->orderByDesc('kickoff_at')
            ->limit(10)
            ->get();

        $h2hSummary = [
            'n' => $h2h->count(),
            'home_wins' => 0,
            'draws' => 0,
            'away_wins' => 0,
            'avg_goals' => null,
            'over_2_5' => null,
            'btts' => null,
        ];

        if ($h2h->count() > 0) {
            $goals = 0;
            $over25 = 0;
            $btts = 0;

            foreach ($h2h as $m) {
                $total = $m->home_score + $m->away_score;
                $goals += $total;
                if ($total > 2.5) {
                    $over25++;
                }
                if ($m->home_score > 0 && $m->away_score > 0) {
                    $btts++;
                }

                if ($m->home_score === $m->away_score) {
                    $h2hSummary['draws']++;
                } else {
                    $winner = $m->home_score > $m->away_score ? $m->home_team_id : $m->away_team_id;
                    if ($winner == $h) {
                        $h2hSummary['home_wins']++;
                    } else {
                        $h2hSummary['away_wins']++;
                    }
                }
            }

            $n = $h2h->count();
            $h2hSummary['avg_goals'] = round($goals / $n, 2);
            $h2hSummary['over_2_5'] = round(100 * $over25 / $n);
            $h2hSummary['btts'] = round(100 * $btts / $n);
        }

        return view('fixture', compact(
            'fixture', 'stats', 'events', 'oddsGroups',
            'home', 'away', 'homeVenue', 'awayVenue', 'expected',
            'h2h', 'h2hSummary'
        ));
    }

    /**
     * Page d'une équipe : forme, stats par saison, derniers matchs.
     */
    public function team(Team $team)
    {
        $recent = DB::table('team_matches as tm')
            ->join('teams as o', 'o.id', '=', 'tm.opponent_id')
            ->join('leagues as l', 'l.id', '=', 'tm.league_id')
            ->where('tm.team_id', $team->id)
            ->orderByDesc('tm.kickoff_at')
            ->limit(15)
            ->select('tm.*', 'o.name as opponent', 'l.name as league_name')
            ->get();

        $seasonStats = DB::table('team_season_stats as s')
            ->join('leagues as l', 'l.id', '=', 's.league_id')
            ->join('seasons as se', 'se.id', '=', 's.season_id')
            ->where('s.team_id', $team->id)
            ->orderByDesc('se.start_date')
            ->orderByDesc('se.id')
            ->select('s.*', 'l.name as league_name', 'se.label as season_label')
            ->get();

        $overall = $this->snapshot($team->id);
        $home = $this->snapshot($team->id, null, 1);
        $away = $this->snapshot($team->id, null, 0);

        return view('team', compact('team', 'recent', 'seasonStats', 'overall', 'home', 'away'));
    }

    /**
     * Photo de la forme d'une équipe sur ses derniers matchs.
     *
     * @param  mixed     $before  ne compter que les matchs avant cette date (null = tous)
     * @param  int|null  $isHome  1 = seulement à domicile, 0 = seulement à l'extérieur, null = tous
     */
    private function snapshot(int $teamId, $before = null, ?int $isHome = null, int $limit = 10): array
    {
        $matches = DB::table('team_matches')
            ->where('team_id', $teamId)
            ->when($before, fn ($q) => $q->where('kickoff_at', '<', $before))
            ->when($isHome !== null, fn ($q) => $q->where('is_home', $isHome))
            ->orderByDesc('kickoff_at')
            ->limit($limit)
            ->get();

        $n = $matches->count();

        $pct = fn (callable $test) => $n ? (int) round(100 * $matches->filter($test)->count() / $n) : null;
        $avg = fn (string $col) => $matches->whereNotNull($col)->count()
            ? round((float) $matches->avg($col), 2)
            : null;

        return [
            'n' => $n,
            'form' => $matches->take(5)->pluck('result')->all(),
            'wins' => $matches->where('result', 'W')->count(),
            'draws' => $matches->where('result', 'D')->count(),
            'losses' => $matches->where('result', 'L')->count(),
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
        ];
    }
}
