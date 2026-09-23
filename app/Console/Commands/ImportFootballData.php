<?php

namespace App\Console\Commands;

use App\Models\Fixture;
use App\Models\League;
use App\Models\Season;
use App\Models\Sport;
use App\Models\Team;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Importe automatiquement les matchs (calendrier + scores) depuis football-data.org.
 *
 * Couverture de l'API gratuite : PL, PD, BL1, SA, FL1, PPL, DED, ELC, CL, EC, WC, BSA.
 * Elle ne fournit PAS de stats de match (tirs, corners...), ni de cotes, ni de joueurs.
 * Les scores et le calendrier peuvent être légèrement décalés (compte gratuit).
 * Limite : 10 requêtes par minute.
 *
 * Utilisation :
 *   php artisan matches:import PL
 *   php artisan matches:import PL --season=2026
 *   php artisan matches:import --all
 */
class ImportFootballData extends Command
{
    protected $signature = 'matches:import
        {code? : Code de la compétition (PL, PD, BL1, SA, FL1, PPL, DED, ELC, CL)}
        {--season= : Année de début de saison, ex. 2026 (par défaut : saison la plus récente de l\'API)}
        {--all : Importer toutes les compétitions couvertes par ce projet}';

    protected $description = 'Importe les matchs et les scores depuis football-data.org';

    /** Compétitions couvertes par l'API gratuite ET déjà créées par CompetitionSeeder. */
    private const SUPPORTED_CODES = ['PL', 'PD', 'BL1', 'SA', 'FL1', 'PPL', 'DED', 'CL'];

    public function handle(): int
    {
        $key = config('services.football_data.key');

        if (! $key) {
            $this->error('Aucune clé API trouvée. Ajoute FOOTBALL_DATA_KEY=ta_cle dans .env, puis relance.');

            return self::FAILURE;
        }

        $codes = $this->option('all') ? self::SUPPORTED_CODES : [$this->argument('code')];

        if ($codes === [null]) {
            $this->error('Précise un code de compétition (ex. PL) ou utilise --all.');
            $this->line('Compétitions gérées ici : ' . implode(', ', self::SUPPORTED_CODES));

            return self::FAILURE;
        }

        foreach ($codes as $i => $code) {
            if (! in_array($code, self::SUPPORTED_CODES, true)) {
                $this->warn("{$code} n'est pas géré par cette commande (voir la liste ci-dessus), ignoré.");

                continue;
            }

            $this->importCompetition($code, $key);

            // Respecte la limite de 10 requêtes/minute quand on enchaîne plusieurs compétitions
            if ($i < count($codes) - 1) {
                sleep(7);
            }
        }

        return self::SUCCESS;
    }

    private function importCompetition(string $code, string $key): void
    {
        $this->info("Import de {$code}...");

        $url = "https://api.football-data.org/v4/competitions/{$code}/matches";
        $query = [];

        if ($seasonYear = $this->option('season')) {
            $query['season'] = $seasonYear;
        }

        $response = Http::withHeaders(['X-Auth-Token' => $key])->get($url, $query);

        if ($response->status() === 429) {
            $this->error('Trop de requêtes envoyées à l\'API (limite : 10/minute). Réessaie dans une minute.');

            return;
        }

        if (! $response->successful()) {
            $this->error("Erreur API ({$response->status()}) pour {$code} : " . $response->body());

            return;
        }

        $payload = $response->json();
        $matches = $payload['matches'] ?? [];

        if (empty($matches)) {
            $this->warn("Aucun match renvoyé par l'API pour {$code}.");

            return;
        }

        $football = Sport::firstOrCreate(['name' => 'Football'], ['slug' => 'football']);
        $league = League::where('code', $code)->first();

        if (! $league) {
            $this->error("Aucune compétition avec le code {$code} dans ta base. Lance d'abord CompetitionSeeder.");

            return;
        }

        $created = 0;
        $updated = 0;
        $teamsCreated = 0;

        foreach ($matches as $m) {
            $season = $this->resolveSeason($league, $m);
            $home = $this->resolveTeam($football, $season, $m['homeTeam'], $teamsCreated);
            $away = $this->resolveTeam($football, $season, $m['awayTeam'], $teamsCreated);

            if (! $home || ! $away) {
                continue;
            }

            $result = $this->upsertFixture($league, $season, $home, $away, $m);
            $result === 'created' ? $created++ : $updated++;
        }

        $this->info("{$code} : {$created} match(s) créé(s), {$updated} mis à jour, {$teamsCreated} équipe(s) créée(s).");
    }

    /**
     * Retrouve ou crée la saison correspondant à la date du match (ex. 2026/2027).
     */
    private function resolveSeason(League $league, array $match): Season
    {
        $date = Carbon::parse($match['utcDate']);
        // La saison européenne commence en juillet : un match en janvier 2027 appartient à la saison 2026/2027
        $startYear = $date->month >= 7 ? $date->year : $date->year - 1;
        $label = "{$startYear}/" . ($startYear + 1);

        $season = Season::where('league_id', $league->id)
            ->whereIn('label', [$label, str_replace('/', '-', $label)])
            ->first();

        if ($season) {
            return $season;
        }

        return Season::create([
            'league_id' => $league->id,
            'label' => $label,
            'start_date' => "{$startYear}-07-01",
            'end_date' => ($startYear + 1) . '-06-30',
        ]);
    }

    /**
     * Retrouve l'équipe par son identifiant API (le plus fiable). À défaut, essaie par nom
     * parmi les équipes déjà associées à la saison, pour éviter les doublons d'orthographe.
     * Si rien ne correspond, crée l'équipe et l'associe à la saison.
     */
    private function resolveTeam(Sport $sport, Season $season, array $apiTeam, int &$createdCounter): ?Team
    {
        $externalId = $apiTeam['id'] ?? null;
        $name = $apiTeam['name'] ?? $apiTeam['shortName'] ?? null;

        if (! $name) {
            return null;
        }

        $team = Team::where('sport_id', $sport->id)->where('external_id', $externalId)->first();

        if (! $team) {
            $seasonTeamIds = DB::table('season_team')->where('season_id', $season->id)->pluck('team_id');

            $team = Team::whereIn('id', $seasonTeamIds)
                ->where(function ($q) use ($name, $apiTeam) {
                    $q->where('name', $name);
                    if (! empty($apiTeam['shortName'])) {
                        $q->orWhere('name', $apiTeam['shortName']);
                    }
                })
                ->first();

            // Dernier recours : rapprochement souple (ignore les accents/espaces)
            if (! $team) {
                $normalized = Str::of($name)->lower()->ascii()->replace(' ', '')->toString();

                $team = Team::whereIn('id', $seasonTeamIds)->get()->first(
                    fn ($t) => Str::of($t->name)->lower()->ascii()->replace(' ', '')->toString() === $normalized
                );
            }
        }

        if (! $team) {
            $team = Team::create([
                'sport_id' => $sport->id,
                'external_id' => $externalId,
                'name' => $name,
                'type' => 'CLUB',
            ]);
            $createdCounter++;
        } elseif ($externalId && ! $team->external_id) {
            // On note l'identifiant API pour la prochaine fois, sans changer le nom que tu as choisi
            $team->update(['external_id' => $externalId]);
        }

        DB::table('season_team')->insertOrIgnore([
            'season_id' => $season->id,
            'team_id' => $team->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $team;
    }

    /**
     * Crée le match, ou met à jour un match déjà connu (par external_id, ou en réclamant
     * un match généré par FixturesSeeder qui a les mêmes équipes et pas encore d'external_id).
     */
    private function upsertFixture(League $league, Season $season, Team $home, Team $away, array $m): string
    {
        $externalId = $m['id'];

        $fixture = Fixture::where('source', 'football-data')->where('external_id', $externalId)->first();
        $isNew = false;

        if (! $fixture) {
            $fixture = Fixture::where('league_id', $league->id)
                ->where('season_id', $season->id)
                ->where('home_team_id', $home->id)
                ->where('away_team_id', $away->id)
                ->whereNull('external_id')
                ->first();
        }

        if (! $fixture) {
            $fixture = new Fixture();
            $isNew = true;
        }

        $full = $m['score']['fullTime'] ?? [];
        $half = $m['score']['halfTime'] ?? [];

        $fixture->fill([
            'league_id' => $league->id,
            'season_id' => $season->id,
            'source' => 'football-data',
            'external_id' => $externalId,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
            'kickoff_at' => Carbon::parse($m['utcDate']),
            'status' => $this->mapStatus($m['status']),
            'stage' => $this->mapStage($m['stage'] ?? null),
            'matchday' => $m['matchday'] ?? null,
            'home_score' => $full['home'] ?? null,
            'away_score' => $full['away'] ?? null,
            'ht_home_score' => $half['home'] ?? null,
            'ht_away_score' => $half['away'] ?? null,
        ])->save();

        return $isNew ? 'created' : 'updated';
    }

    private function mapStatus(string $apiStatus): string
    {
        return match ($apiStatus) {
            'FINISHED' => 'FINISHED',
            'IN_PLAY', 'PAUSED' => 'LIVE',
            'POSTPONED' => 'POSTPONED',
            'CANCELLED', 'SUSPENDED' => 'CANCELLED',
            default => 'SCHEDULED',
        };
    }

    private function mapStage(?string $apiStage): ?string
    {
        return match ($apiStage) {
            'REGULAR_SEASON' => 'REGULAR_SEASON',
            'GROUP_STAGE', 'LEAGUE_STAGE' => 'GROUP_STAGE',
            'LAST_16' => 'ROUND_OF_16',
            'QUARTER_FINALS' => 'QUARTER_FINAL',
            'SEMI_FINALS' => 'SEMI_FINAL',
            'THIRD_PLACE' => 'THIRD_PLACE',
            'FINAL' => 'FINAL',
            default => null,
        };
    }
}
